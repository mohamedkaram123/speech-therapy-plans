# Strips the GDEF MarkFilteringSet lookup flag (0x0010) that mPDF refuses to load.
# Usage: python3 scripts/patch_mpdf_font.py <in.ttf> <out.ttf>   (needs `pip install fonttools`)
import sys
from fontTools.ttLib import TTFont
src, dst = sys.argv[1], sys.argv[2]
f = TTFont(src)
n = 0
for tag in ('GSUB', 'GPOS'):
    if tag in f:
        for lk in f[tag].table.LookupList.Lookup:
            if lk.LookupFlag & 0x0010:
                lk.LookupFlag &= ~0x0010
                if hasattr(lk, 'MarkFilteringSet'):
                    lk.MarkFilteringSet = None
                n += 1
print(tag, 'patched lookups:', n)
f.save(dst)
