import openpyxl
import pymysql
import sys

def parse_coord(s):
    if not s or str(s) in ('NULL', 'None', ''):
        return None, None
    parts = str(s).split(',')
    if len(parts) >= 2:
        try:
            return float(parts[0].strip()), float(parts[1].strip())
        except:
            return None, None
    return None, None

def nullify(v):
    if v is None or str(v) in ('NULL', 'None', ''):
        return None
    return str(v)[:500] if v else None

print('Connecting to MySQL...')
conn = pymysql.connect(host='127.0.0.1', port=3306, user='root', password='', database='support_map_db', charset='utf8mb4')
cursor = conn.cursor()

print('Reading Excel Sheet2...')
wb = openpyxl.load_workbook(r'c:\xampp\htdocs\ALATTEMPUR\TIKORSEMIGOOGLE\Upload 21 Ags 2026.xlsx', read_only=True, data_only=True)
ws = wb.worksheets[1]

success = 0
fail = 0
batch = []
BATCH_SIZE = 200

sql = '''INSERT INTO tikor 
    (homepass_id, project_id, region, sub_region, provinsi, kota, kecamatan, kelurahan, kode_pos,
     homepassed_koordinat, lat, lng, resident_type, resident_name, nama_jalan, no_rumah, unit,
     pop_id, splitter_id, spliter_distribusi_koordinat, splitter_lat, splitter_lng,
     remark, rfs_status, homepass_status, cluster_name, submission_date, last_update)
    VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
    ON DUPLICATE KEY UPDATE lat=VALUES(lat), lng=VALUES(lng), homepass_status=VALUES(homepass_status)'''

for i, row in enumerate(ws.iter_rows(values_only=True)):
    if i == 0:
        continue  # skip header
    try:
        coord = nullify(row[9])
        lat, lng = parse_coord(coord)
        scoord = nullify(row[17])
        slat, slng = parse_coord(scoord)
        
        # Safely get row values (row may be shorter than expected)
        def get(idx, default=None):
            try:
                return nullify(row[idx])
            except:
                return default
        
        data = (
            get(0), get(1), get(2), get(3), get(4), get(5), get(6), get(7), get(8),
            coord, lat, lng,
            get(10), get(11), get(12), get(13), get(14), get(15), get(16),
            scoord, slat, slng,
            get(18), get(21), get(41), get(39), get(22), get(24)
        )
        batch.append(data)
        
        if len(batch) >= BATCH_SIZE:
            cursor.executemany(sql, batch)
            conn.commit()
            success += len(batch)
            print(f'Imported {success} rows...')
            batch = []
    except Exception as e:
        fail += 1
        if fail <= 3:
            print(f'Row {i} error: {e}')

if batch:
    cursor.executemany(sql, batch)
    conn.commit()
    success += len(batch)

print(f'DONE! Success: {success}, Fail: {fail}')
conn.close()
