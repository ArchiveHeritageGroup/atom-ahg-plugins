#!/bin/bash

PLUGIN_DIR="/usr/share/nginx/archive/plugins/arExtendedRightsPlugin"

# RightsStatements.org icons
cd "${PLUGIN_DIR}/images/rights-statements"
for code in InC InC-OW-EU InC-EDU InC-NC InC-RUU NoC-CR NoC-NC NoC-OKLR NoC-US CNE UND NKC; do
    wget -q "https://rightsstatements.org/files/buttons/${code}.dark-white-interior.png" -O "${code}.png" 2>/dev/null || echo "Manual download needed for ${code}"
done

# Creative Commons icons
cd "${PLUGIN_DIR}/images/creative-commons"
wget -q "https://mirrors.creativecommons.org/presskit/icons/cc.png" -O "cc.png"
wget -q "https://mirrors.creativecommons.org/presskit/icons/by.png" -O "by.png"
wget -q "https://mirrors.creativecommons.org/presskit/icons/nc.png" -O "nc.png"
wget -q "https://mirrors.creativecommons.org/presskit/icons/nd.png" -O "nd.png"
wget -q "https://mirrors.creativecommons.org/presskit/icons/sa.png" -O "sa.png"
wget -q "https://mirrors.creativecommons.org/presskit/icons/zero.png" -O "zero.png"

echo "Icons downloaded. TK Labels must be downloaded manually from localcontexts.org"
