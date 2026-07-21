import json
import sys
import zipfile
from copy import deepcopy
from io import BytesIO
from pathlib import Path
from xml.etree import ElementTree as ET


WORD_NAMESPACE = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
XML_NAMESPACE = "http://www.w3.org/XML/1998/namespace"
W = f"{{{WORD_NAMESPACE}}}"


def paragraph_text(paragraph) -> str:
    return "".join(node.text or "" for node in paragraph.findall(f".//{W}t"))


def register_document_namespaces(document_xml: bytes) -> dict:
    namespaces = {}
    registered_prefixes = set()
    for _, namespace in ET.iterparse(BytesIO(document_xml), events=("start-ns",)):
        prefix, uri = namespace
        namespaces[prefix] = uri
        if prefix in registered_prefixes:
            continue
        try:
            ET.register_namespace(prefix, uri)
            registered_prefixes.add(prefix)
        except ValueError:
            # Reserved prefixes are already handled by ElementTree.
            continue
    return namespaces


def preserve_namespace_declarations(document_xml: bytes, namespaces: dict) -> bytes:
    root_start = document_xml.find(b"<w:document")
    root_end = document_xml.find(b">", root_start)
    if root_start < 0 or root_end < 0:
        return document_xml

    root_tag = document_xml[root_start:root_end]
    declarations = []
    for prefix, uri in namespaces.items():
        if not prefix or prefix == "xml":
            continue
        declaration_key = f"xmlns:{prefix}=".encode("utf-8")
        if declaration_key not in root_tag:
            declarations.append(f' xmlns:{prefix}="{uri}"'.encode("utf-8"))

    if not declarations:
        return document_xml
    return document_xml[:root_end] + b"".join(declarations) + document_xml[root_end:]


def append_formatted_run(paragraph, text: str, bold: bool = False) -> None:
    run = ET.SubElement(paragraph, f"{W}r")
    run_properties = ET.SubElement(run, f"{W}rPr")
    fonts = ET.SubElement(run_properties, f"{W}rFonts")
    fonts.set(f"{W}ascii", "Helvetica")
    fonts.set(f"{W}hAnsi", "Helvetica")
    fonts.set(f"{W}cs", "Helvetica")
    if bold:
        ET.SubElement(run_properties, f"{W}b")
    font_size = ET.SubElement(run_properties, f"{W}sz")
    font_size.set(f"{W}val", "20")
    text_node = ET.SubElement(run, f"{W}t")
    if text.startswith(" ") or text.endswith(" "):
        text_node.set(f"{{{XML_NAMESPACE}}}space", "preserve")
    text_node.text = text


def set_labeled_field(body, label: str, value: str) -> None:
    paragraph = next(
        (
            child
            for child in body
            if child.tag == f"{W}p" and paragraph_text(child).strip().startswith(label)
        ),
        None,
    )
    if paragraph is None:
        raise RuntimeError(f"Template field not found: {label}")

    source_run = paragraph.find(f"{W}r")
    tab_run = next((run for run in paragraph.findall(f"{W}r") if run.find(f"{W}tab") is not None), None)
    if tab_run is not None:
        remove_content = False
        for child in list(paragraph):
            if remove_content:
                paragraph.remove(child)
            elif child is tab_run:
                remove_content = True

    new_run = ET.Element(f"{W}r")
    if source_run is not None:
        run_properties = source_run.find(f"{W}rPr")
        if run_properties is not None:
            new_run.append(deepcopy(run_properties))
    text_node = ET.SubElement(new_run, f"{W}t")
    text_node.text = value
    paragraph.append(new_run)


def replace_body_paragraph(body, starts_with: str, replacement: str) -> None:
    paragraph = next(
        (
            child
            for child in body
            if child.tag == f"{W}p" and paragraph_text(child).strip().startswith(starts_with)
        ),
        None,
    )
    if paragraph is None:
        raise RuntimeError(f"Template paragraph not found: {starts_with}")
    set_paragraph_segments(paragraph, [(replacement, False)])


def set_paragraph_segments(paragraph, segments) -> None:
    for child in list(paragraph):
        if child.tag != f"{W}pPr":
            paragraph.remove(child)
    for text, bold in segments:
        append_formatted_run(paragraph, str(text), bool(bold))


def first_cell_paragraph(row):
    paragraph = row.find(f"./{W}tc/{W}p")
    if paragraph is None:
        raise RuntimeError("The memo template contains an invalid table row.")
    return paragraph


def populate_memo_xml(document_xml: bytes, values: dict) -> bytes:
    namespaces = register_document_namespaces(document_xml)
    root = ET.fromstring(document_xml)
    body = root.find(f"{W}body")
    if body is None:
        raise RuntimeError("The memo template does not contain a document body.")

    warning_type = {
        "Verbal Memo": "Verbal Warning",
        "Written Memo": "Written Warning",
        "Final Memo": "Final Warning",
    }.get(values["memo_action"], "Warning")
    warning_type_lower = warning_type.lower()

    set_labeled_field(body, "TO:", values["user_name"])
    set_labeled_field(body, "SUBJECT:", f"{warning_type} – User Error")
    set_labeled_field(body, "DATE:", values["date_recorded"])
    replace_body_paragraph(
        body,
        "This memo serves as a formal documentation",
        f"This memo serves as a formal documentation of a {warning_type_lower} regarding:",
    )
    replace_body_paragraph(
        body,
        "By signing below, you acknowledge",
        "By signing below, you acknowledge that this "
        f"{warning_type_lower} was delivered and that you understand the expectations for improvement.",
    )

    tables = [child for child in body if child.tag == f"{W}tbl"]
    if len(tables) < 2:
        raise RuntimeError("The memo template detail or remarks table is missing.")

    details_table = tables[0]
    detail_rows = details_table.findall(f"./{W}tr")
    remarks_rows = tables[1].findall(f"./{W}tr")
    if len(detail_rows) < 3 or not remarks_rows:
        raise RuntimeError("The memo template tables do not have the expected rows.")

    set_paragraph_segments(
        first_cell_paragraph(detail_rows[0]),
        [
            ("Reference Number: ", True),
            (values["reference_number"], False),
            ("   |   Date of Transaction: ", True),
            (values["transaction_date"], False),
            ("   |   Branch: ", True),
            (values["branch"], False),
            ("   |   Module: ", True),
            (values["module"], False),
        ],
    )
    set_paragraph_segments(
        first_cell_paragraph(detail_rows[1]),
        [("Amount: ", True), (values["amount"], False)],
    )
    set_paragraph_segments(
        first_cell_paragraph(detail_rows[2]),
        [("Reason: ", True), (values["reason"], False)],
    )
    for extra_row in detail_rows[3:]:
        details_table.remove(extra_row)

    set_paragraph_segments(
        first_cell_paragraph(remarks_rows[0]),
        [("Remarks: ", True), (values["remarks"], False)],
    )

    populated_xml = ET.tostring(root, encoding="utf-8", xml_declaration=True)
    populated_xml = populated_xml.replace(
        b"<?xml version='1.0' encoding='utf-8'?>",
        b'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>',
        1,
    )
    return preserve_namespace_declarations(populated_xml, namespaces)


def build_from_template(payload: dict) -> None:
    output_path = Path(payload["output"])
    template_path = Path(payload["template"])
    values = payload["memo_values"]

    with zipfile.ZipFile(template_path, "r") as source:
        document_xml = source.read("word/document.xml")
        populated_xml = populate_memo_xml(document_xml, values)
        with zipfile.ZipFile(output_path, "w") as destination:
            for item in source.infolist():
                content = populated_xml if item.filename == "word/document.xml" else source.read(item.filename)
                destination.writestr(item, content)


def build_from_files(payload: dict) -> None:
    output_path = Path(payload["output"])
    files = payload["files"]
    with zipfile.ZipFile(output_path, "w", compression=zipfile.ZIP_DEFLATED) as archive:
        for archive_name, content in files.items():
            archive.writestr(archive_name, content)


def main() -> int:
    if len(sys.argv) != 2:
        print("Usage: export_memo_docx_helper.py <payload.json>", file=sys.stderr)
        return 2

    payload_path = Path(sys.argv[1])
    with payload_path.open("r", encoding="utf-8-sig") as handle:
        payload = json.load(handle)

    if "template" in payload:
        build_from_template(payload)
    else:
        build_from_files(payload)

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
