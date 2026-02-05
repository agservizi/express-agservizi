from pathlib import Path
from datetime import date

pdf_path = Path('/Users/carminecavaliere/Desktop/express-app/staging-express/public/assets/kits/avvio-store-kit.pdf')

COLOR_HEADER = (0.06, 0.09, 0.16)  # #0f172a
COLOR_ACCENT = (0.15, 0.39, 0.92)  # #2563eb
COLOR_TEXT = (0.06, 0.09, 0.16)
COLOR_MUTED = (0.28, 0.33, 0.42)   # #475569
COLOR_BORDER = (0.89, 0.91, 0.94)  # #e2e8f0

PAGE_W, PAGE_H = 595, 842
MARGIN_X = 56

content_lines = []


def rgb_fill(r, g, b):
    content_lines.append(f"{r:.3f} {g:.3f} {b:.3f} rg")


def rgb_stroke(r, g, b):
    content_lines.append(f"{r:.3f} {g:.3f} {b:.3f} RG")


def rect(x, y, w, h, fill=True, stroke=False):
    op = 'f' if fill and not stroke else 'S' if stroke and not fill else 'B'
    content_lines.append(f"{x:.2f} {y:.2f} {w:.2f} {h:.2f} re {op}")


def text(x, y, txt, size=12, font='F1', color=COLOR_TEXT):
    r, g, b = color
    content_lines.append("BT")
    content_lines.append(f"/{font} {size} Tf {r:.3f} {g:.3f} {b:.3f} rg {x:.2f} {y:.2f} Td")
    safe = txt.replace('\\', r'\\').replace('(', r'\(').replace(')', r'\)')
    content_lines.append(f"({safe}) Tj")
    content_lines.append("ET")


def wrap_text(txt, max_width, size=12, font='F1'):
    char_w = size * (0.6 if font == 'F3' else 0.52)
    max_chars = max(1, int(max_width / char_w))
    words = txt.split(' ')
    lines = []
    line = ''
    for w in words:
        if not line:
            line = w
            continue
        if len(line) + 1 + len(w) <= max_chars:
            line += ' ' + w
        else:
            lines.append(line)
            line = w
    if line:
        lines.append(line)
    return lines


def paragraph(x, y, txt, max_width, size=12, leading=16, font='F1', color=COLOR_TEXT):
    lines = wrap_text(txt, max_width, size=size, font=font)
    cursor = y
    for line in lines:
        text(x, cursor, line, size=size, font=font, color=color)
        cursor -= leading
    return cursor


# Header
rgb_fill(*COLOR_HEADER)
rect(0, PAGE_H - 140, PAGE_W, 140, fill=True)
text(MARGIN_X, PAGE_H - 70, 'Kit gratuito "Avvio Store"', size=22, font='F2', color=(1, 1, 1))
text(MARGIN_X, PAGE_H - 95, 'Checklist operativa + template report', size=12, font='F1', color=(0.82, 0.86, 0.93))
text(MARGIN_X, PAGE_H - 115, f'Aggiornato al {date.today().strftime("%d/%m/%Y")}', size=10, font='F1', color=(0.64, 0.67, 0.74))

# Body intro
cursor = PAGE_H - 170
text(MARGIN_X, cursor, 'Obiettivo', size=14, font='F2', color=COLOR_ACCENT)
cursor -= 18
cursor = paragraph(
    MARGIN_X,
    cursor,
    'Una guida rapida per avviare il tuo store con processi chiari e margini sotto controllo fin dal primo giorno.',
    PAGE_W - 2 * MARGIN_X,
    size=12,
    leading=16,
    font='F1',
    color=COLOR_TEXT,
)

# Checklist box
cursor -= 18
box_y = cursor - 220
rgb_fill(0.98, 0.99, 1)
rect(MARGIN_X, box_y, PAGE_W - 2 * MARGIN_X, 210, fill=True)
rgb_stroke(*COLOR_BORDER)
rect(MARGIN_X, box_y, PAGE_W - 2 * MARGIN_X, 210, fill=False, stroke=True)
text(MARGIN_X + 16, cursor - 10, 'Checklist operativa', size=13, font='F2', color=COLOR_TEXT)

check_items = [
    'Definisci il catalogo SIM e operatori disponibili',
    'Importa listini prodotti e servizi',
    'Configura aliquote IVA e diciture scontrino',
    'Imposta ruoli utenti e permessi',
    'Attiva alert stock minimi e soglie di riordino',
    'Verifica flusso vendita (SIM + prodotto + sconti)',
    'Configura report giornalieri e KPI principali',
    'Esegui una vendita di prova end-to-end',
]

item_y = cursor - 36
for item in check_items:
    text(MARGIN_X + 18, item_y, '-', size=12, font='F2', color=COLOR_ACCENT)
    item_y = paragraph(MARGIN_X + 30, item_y, item, PAGE_W - 2 * MARGIN_X - 40, size=11, leading=15, font='F1', color=COLOR_TEXT)

cursor = box_y - 30

# Report template
text(MARGIN_X, cursor, 'Template report (CSV)', size=14, font='F2', color=COLOR_ACCENT)
cursor -= 18
cursor = paragraph(
    MARGIN_X,
    cursor,
    'Copia il blocco CSV qui sotto in un file .csv e compila una riga per ogni giornata o punto vendita.',
    PAGE_W - 2 * MARGIN_X,
    size=11,
    leading=15,
    font='F1',
    color=COLOR_MUTED,
)

# CSV box
cursor -= 10
csv_box_height = 140
csv_box_y = cursor - csv_box_height
rgb_fill(1, 1, 1)
rect(MARGIN_X, csv_box_y, PAGE_W - 2 * MARGIN_X, csv_box_height, fill=True)
rgb_stroke(*COLOR_BORDER)
rect(MARGIN_X, csv_box_y, PAGE_W - 2 * MARGIN_X, csv_box_height, fill=False, stroke=True)

csv_lines = [
    'Data,Punto vendita,Vendite totali,Incasso lordo,Incasso netto,Sconti,Resi,Nuovi clienti,Stock SIM',
    '2026-02-05,Store Milano,0,0,0,0,0,0,0,0,',
]

csv_y = cursor - 18
csv_text_width = PAGE_W - 2 * MARGIN_X - 24
for line in csv_lines:
    csv_y = paragraph(
        MARGIN_X + 12,
        csv_y,
        line,
        csv_text_width,
        size=8,
        leading=12,
        font='F3',
        color=COLOR_TEXT,
    )
    csv_y -= 8

# Footer
text(MARGIN_X, 40, 'Coresuite Express · Kit Avvio Store', size=9, font='F1', color=COLOR_MUTED)
text(PAGE_W - MARGIN_X - 120, 40, 'www.agenziaplinio.it', size=9, font='F1', color=COLOR_MUTED)

content = "\n".join(content_lines)

objects = []
objects.append("1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n")
objects.append("2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n")
objects.append("3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R /F3 6 0 R >> >> /Contents 7 0 R >>\nendobj\n")
objects.append("4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n")
objects.append("5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n")
objects.append("6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>\nendobj\n")
objects.append(f"7 0 obj\n<< /Length {len(content.encode('utf-8'))} >>\nstream\n{content}\nendstream\nendobj\n")

pdf = ["%PDF-1.4\n"]
offsets = [0]
for obj in objects:
    offsets.append(sum(len(p.encode('utf-8')) for p in pdf))
    pdf.append(obj)

xref_start = sum(len(p.encode('utf-8')) for p in pdf)

xref = ["xref\n", f"0 {len(objects) + 1}\n", "0000000000 65535 f \n"]
for off in offsets[1:]:
    xref.append(f"{off:010d} 00000 n \n")

trailer = [
    "trailer\n",
    f"<< /Size {len(objects) + 1} /Root 1 0 R >>\n",
    "startxref\n",
    f"{xref_start}\n",
    "%%EOF\n",
]

pdf.extend(xref)
pdf.extend(trailer)

pdf_path.write_bytes("".join(pdf).encode('utf-8'))
print(f"PDF aggiornato: {pdf_path}")
