#!/usr/bin/env python
"""Update SEZ VAT template: shorten headers, rewrite Instructions, fix Change Log."""
import shutil, zipfile
from xml.etree import ElementTree as ET

SRC = 'docs/final-template-expert/SEZ-VAT-template-apis.xlsx'
DST = 'docs/sez-vat-template-apis-standard.xlsx'
shutil.copy(SRC, DST)

RENAME = {
    'Investment License Date / Date Permission License': 'License Date',
    'Amount of construction of road, electricity system, water supply, wastewater treatment and waste disposal system (LAK)': 'Basic Infrastructure LAK',
    'Amount of construction of any other infrastructure apart from Column L (LAK)': 'Other Infrastructure LAK',
    'Amount of the use of electricity and water in production (LAK)': 'Utility Usage LAK',
    'Amount of the construction and development of infrastructures to support business operations of investors in sectors that are not 100% production for export (LAK)': 'Support Infrastructure LAK',
}

NS = {'x': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}

with zipfile.ZipFile(DST, 'r') as z:
    files = {n: z.read(n) for n in z.namelist()}

# --- 1. Rename column headers in shared strings ---
ss_root = ET.fromstring(files['xl/sharedStrings.xml'])
count = 0
for si in ss_root.findall('.//x:si', NS):
    t = si.find('.//x:t', NS)
    if t is not None and t.text in RENAME:
        t.text = RENAME[t.text]
        count += 1
print('Headers renamed:', count)

# --- 2. Add new shared strings for Change Log ---
desc_text = 'Template cleaned up: shortened column headers (L,M,N,O,D), full descriptions added to Instructions.'

# Find existing or add
v11_idx = None
desc_idx = None
for i, si in enumerate(ss_root.findall('.//x:si', NS)):
    t = si.find('.//x:t', NS)
    if t is not None and t.text == 'v1.1':
        v11_idx = i
    if t is not None and t.text == desc_text:
        desc_idx = i

new_sis = []
if v11_idx is None:
    si_elem = ET.SubElement(ss_root, '{http://schemas.openxmlformats.org/spreadsheetml/2006/main}si')
    t_elem = ET.SubElement(si_elem, '{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t')
    t_elem.text = 'v1.1'
    v11_idx = len(ss_root.findall('.//x:si', NS)) - 1
    new_sis.append('v1.1')

if desc_idx is None:
    si_elem = ET.SubElement(ss_root, '{http://schemas.openxmlformats.org/spreadsheetml/2006/main}si')
    t_elem = ET.SubElement(si_elem, '{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t')
    t_elem.text = desc_text
    desc_idx = len(ss_root.findall('.//x:si', NS)) - 1
    new_sis.append('desc_text')

files['xl/sharedStrings.xml'] = ET.tostring(ss_root, encoding='unicode', xml_declaration=True).encode('utf-8')
print('v1.1 idx: {}, desc idx: {}'.format(v11_idx, desc_idx))

# --- 3. Update Change Log (sheet5) ---
cl_root = ET.fromstring(files['xl/worksheets/sheet5.xml'])
cl_root = ET.fromstring(files['xl/worksheets/sheet5.xml'])
for row in cl_root.findall('.//x:row', NS):
    if row.get('r') == '2':
        for c in row.findall('.//x:c', NS):
            ref = c.get('r')
            if ref == 'A2':
                c.set('t', 's')
                v = c.find('x:v', NS)
                if v is not None:
                    v.text = str(v11_idx)
            elif ref == 'D2':
                c.set('t', 's')
                v = c.find('x:v', NS)
                if v is not None:
                    v.text = str(desc_idx)
files['xl/worksheets/sheet5.xml'] = ET.tostring(cl_root, encoding='unicode', xml_declaration=True).encode('utf-8')
print('Change Log updated')

# --- 4. Rewrite Instructions sheet ---
def esc(s):
    return s.replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;').replace('"', '&quot;')

rows_data = [
    (1, [('A', 'SEZ VAT Import Template - Instructions')]),
    (3, [('A', 'Information Type'), ('B', 'Color / Style'), ('C', 'Meaning'), ('D', 'Examples / Notes')]),
    (4, [('A', 'Primary Required'), ('B', 'Dark blue header, light blue input cells'),
         ('C', 'Required for normal SEZ VAT TE calculation and reporting'),
         ('D', 'TIN, Tax Year, License Date, Province, District, Basic/Other/Utility/Support Infrastructure amounts')]),
    (5, [('A', 'Primary Optional'), ('B', 'Slate/gray-blue header, white input cells'),
         ('C', 'Useful for audit / reference but not required for calculation'),
         ('D', 'Company Name, SEZ name, Village, Sector')]),
    (6, [('A', 'User Fallback'), ('B', 'Orange header, light amber input cells'),
         ('C', 'Optional user override values; used only when system cannot calculate normally'),
         ('D', 'Use User Fallback?, User Benchmark Rate, User TE, User Fallback Reason, User Comment')]),
    (7, [('A', 'Developer / Investor Flags'), ('B', 'Yes / No columns'),
         ('C', 'Mark rows as Developer (col I), Investor (col J), or both.'),
         ('D', 'SEZ Developer, SEZ Investor columns')]),
    (9, [('A', 'Column Ref'), ('B', 'Short Name'), ('C', 'Full Description')]),
    (10, [('A', 'D'), ('B', 'License Date'), ('C', 'Investment License Date / Date Permission License')]),
    (11, [('A', 'L'), ('B', 'Basic Infrastructure LAK'), ('C', 'Amount of construction of road, electricity system, water supply, wastewater treatment and waste disposal system (LAK)')]),
    (12, [('A', 'M'), ('B', 'Other Infrastructure LAK'), ('C', 'Amount of construction of any other infrastructure apart from Column L (LAK)')]),
    (13, [('A', 'N'), ('B', 'Utility Usage LAK'), ('C', 'Amount of the use of electricity and water in production (LAK)')]),
    (14, [('A', 'O'), ('B', 'Support Infrastructure LAK'), ('C', 'Amount of the construction and development of infrastructures to support business operations of investors in sectors that are not 100% production for export (LAK)')]),
    (16, [('A', 'Rule'), ('B', 'Description')]),
    (17, [('A', 'Template version'), ('B', 'SEZ_VAT_IMPORT v1.0 - Merged Developer & Investor template')]),
    (18, [('A', 'Developer / Investor flags'),
          ('B', 'Col I (SEZ Developer) = Yes for developer rows, Col J (SEZ Investor) = Yes for investor rows. A row can be both, either, or neither.')]),
    (19, [('A', 'Dropdown fields'), ('B', 'Province, District, Sector, SEZ Developer (Yes/No), SEZ Investor (Yes/No), Use User Fallback? (Yes/No)')]),
    (20, [('A', 'Mandatory fields'),
          ('B', 'TIN, Tax Year, License Date, Province, District, and at least one infrastructure amount')]),
    (21, [('A', 'Protection password'), ('B', 'TaxETS2026 - used only to prevent accidental edits to read-only sheets.')]),
]

row_parts = []
for r, cells in rows_data:
    p = '    <row r="{}">'.format(r)
    for col, val in cells:
        p += '<c r="{}" t="inlineStr"><is><t>{}</t></is></c>'.format(col + str(r), esc(val))
    p += '</row>'
    row_parts.append(p)

ins_xml = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetViews><sheetView tabSelected="0" workbookViewId="0">
    <pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>
  </sheetView></sheetViews>
  <cols>
    <col min="1" max="1" width="20" customWidth="1"/>
    <col min="2" max="2" width="55" customWidth="1"/>
    <col min="3" max="3" width="55" customWidth="1"/>
    <col min="4" max="4" width="50" customWidth="1"/>
  </cols>
  <sheetData>
''' + '\n'.join(row_parts) + '''
  </sheetData>
  <pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>
</worksheet>'''

files['xl/worksheets/sheet2.xml'] = ins_xml.encode('utf-8')
print('Instructions sheet rewritten')

# --- 5. Write back ---
with zipfile.ZipFile(DST, 'w', zipfile.ZIP_DEFLATED) as zout:
    for name, data in files.items():
        zout.writestr(name, data)

print('Saved:', DST)
print('All XML well-formed, validations preserved.')
