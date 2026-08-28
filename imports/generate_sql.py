import openpyxl

wb = openpyxl.load_workbook(r'Upload 21 Ags 2026.xlsx', read_only=True, data_only=True)
ws = wb.worksheets[1]

def esc(val):
    if val is None or str(val).strip() in ('', 'None', 'NULL', 'null'):
        return 'NULL'
    s = str(val).replace('\\', '\\\\').replace("'", "\\'").replace('\r', '').replace('\n', ' ').strip()
    return f"'{s}'"

def parse_coord(coord_str):
    if not coord_str or str(coord_str).strip() in ('NULL', 'None', ''):
        return 'NULL', 'NULL'
    parts = str(coord_str).split(',')
    if len(parts) >= 2:
        try:
            lat = float(parts[0].strip())
            lng = float(parts[1].strip())
            return str(lat), str(lng)
        except:
            return 'NULL', 'NULL'
    return 'NULL', 'NULL'

with open('import_tikor_data.sql', 'w', encoding='utf-8') as f:
    f.write('-- TIKOR Data Import: 12830 rows\n')
    f.write('SET FOREIGN_KEY_CHECKS=0;\n')
    f.write('SET AUTOCOMMIT=0;\n\n')
    
    rows_buffer = []
    batch_size = 200
    total = 0
    
    for i, row in enumerate(ws.iter_rows(values_only=True)):
        if i == 0:
            continue
        
        def g(idx):
            return row[idx] if idx < len(row) else None
        
        coord = str(g(9)).strip() if g(9) is not None else None
        lat, lng = parse_coord(coord)
        scoord = str(g(17)).strip() if g(17) is not None else None
        slat, slng = parse_coord(scoord)
        
        vals = [
            esc(g(0)), esc(g(1)), esc(g(2)), esc(g(3)), esc(g(4)), esc(g(5)), esc(g(6)), esc(g(7)), esc(g(8)),
            esc(coord), lat, lng,
            esc(g(10)), esc(g(11)), esc(g(12)), esc(g(13)), esc(g(14)), esc(g(15)), esc(g(16)),
            esc(scoord), slat, slng,
            esc(g(18)), esc(g(21)), esc(g(41)), esc(g(39)), esc(g(22)), esc(g(24))
        ]
        
        rows_buffer.append('(' + ', '.join(vals) + ')')
        total += 1
        
        if len(rows_buffer) >= batch_size:
            f.write('INSERT INTO `tikor` (`homepass_id`, `project_id`, `region`, `sub_region`, `provinsi`, `kota`, `kecamatan`, `kelurahan`, `kode_pos`, `homepassed_koordinat`, `lat`, `lng`, `resident_type`, `resident_name`, `nama_jalan`, `no_rumah`, `unit`, `pop_id`, `splitter_id`, `spliter_distribusi_koordinat`, `splitter_lat`, `splitter_lng`, `remark`, `rfs_status`, `homepass_status`, `cluster_name`, `submission_date`, `last_update`) VALUES\n')
            f.write(',\n'.join(rows_buffer))
            f.write('\nON DUPLICATE KEY UPDATE `lat`=VALUES(`lat`), `lng`=VALUES(`lng`), `homepass_status`=VALUES(`homepass_status`);\n\n')
            rows_buffer = []
    
    if rows_buffer:
        f.write('INSERT INTO `tikor` (`homepass_id`, `project_id`, `region`, `sub_region`, `provinsi`, `kota`, `kecamatan`, `kelurahan`, `kode_pos`, `homepassed_koordinat`, `lat`, `lng`, `resident_type`, `resident_name`, `nama_jalan`, `no_rumah`, `unit`, `pop_id`, `splitter_id`, `spliter_distribusi_koordinat`, `splitter_lat`, `splitter_lng`, `remark`, `rfs_status`, `homepass_status`, `cluster_name`, `submission_date`, `last_update`) VALUES\n')
        f.write(',\n'.join(rows_buffer))
        f.write('\nON DUPLICATE KEY UPDATE `lat`=VALUES(`lat`), `lng`=VALUES(`lng`), `homepass_status`=VALUES(`homepass_status`);\n\n')
    
    f.write('COMMIT;\n')
    f.write('SET FOREIGN_KEY_CHECKS=1;\n')

print(f'Done! Exported {total} rows to import_tikor_data.sql')
