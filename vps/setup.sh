#!/bin/bash
# FreeAi4Me - Setup VPS (Ubuntu/Debian)
# Uruchom jako root: sudo bash setup.sh twoja-domena.pl

set -e

DOMAIN=$1
TUNNEL_PORT=${2:-8080}
API_KEY=${3:-""}

# Kolory
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}============================================${NC}"
echo -e "${CYAN}   FreeAi4Me - VPS Setup${NC}"
echo -e "${CYAN}============================================${NC}"
echo ""

# Sprawdz argumenty
if [ -z "$DOMAIN" ]; then
    echo -e "${RED}Uzycie: sudo bash setup.sh <domena> [port_tunelu] [api_key]${NC}"
    echo -e "${YELLOW}Przyklad: sudo bash setup.sh ai.mojadomena.pl 8080 moj-sekretny-klucz${NC}"
    exit 1
fi

echo -e "${CYAN}Konfiguracja:${NC}"
echo -e "  Domena: ${GREEN}$DOMAIN${NC}"
echo -e "  Port tunelu: ${GREEN}$TUNNEL_PORT${NC}"
echo -e "  API Key: ${GREEN}${API_KEY:-'(brak - publiczny dostep)'}${NC}"
echo ""

# 1. Aktualizacja systemu
echo -e "${YELLOW}[1/5] Aktualizacja systemu...${NC}"
apt-get update -qq
apt-get upgrade -y -qq

# 2. Instalacja nginx i certbot
echo -e "${YELLOW}[2/5] Instalacja nginx i certbot...${NC}"
apt-get install -y -qq nginx certbot python3-certbot-nginx

# 3. Konfiguracja SSH (GatewayPorts)
echo -e "${YELLOW}[3/5] Konfiguracja SSH...${NC}"

# Backup sshd_config
cp /etc/ssh/sshd_config /etc/ssh/sshd_config.backup

# Wlacz GatewayPorts dla tunelu
if ! grep -q "^GatewayPorts" /etc/ssh/sshd_config; then
    echo "GatewayPorts clientspecified" >> /etc/ssh/sshd_config
fi

# Keepalive
if ! grep -q "^ClientAliveInterval" /etc/ssh/sshd_config; then
    echo "ClientAliveInterval 60" >> /etc/ssh/sshd_config
    echo "ClientAliveCountMax 3" >> /etc/ssh/sshd_config
fi

systemctl restart sshd

# 4. Konfiguracja nginx
echo -e "${YELLOW}[4/5] Konfiguracja nginx...${NC}"

# Stworz konfiguracje nginx
cat > /etc/nginx/sites-available/freeai4me << EOF
# FreeAi4Me - OpenAI Compatible API Proxy
# Domena: $DOMAIN

upstream lm_studio {
    server 127.0.0.1:$TUNNEL_PORT;
    keepalive 32;
}

server {
    listen 80;
    server_name $DOMAIN;

    # Przekierowanie na HTTPS (certbot doda to automatycznie)
    location / {
        return 301 https://\$server_name\$request_uri;
    }
}

server {
    listen 443 ssl http2;
    server_name $DOMAIN;

    # SSL - certbot uzupelni
    # ssl_certificate /etc/letsencrypt/live/$DOMAIN/fullchain.pem;
    # ssl_certificate_key /etc/letsencrypt/live/$DOMAIN/privkey.pem;

    # Bezpieczenstwo
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;

    # Limity
    client_max_body_size 100M;

    # Logi
    access_log /var/log/nginx/freeai4me.access.log;
    error_log /var/log/nginx/freeai4me.error.log;

    # Health check
    location /health {
        return 200 'OK';
        add_header Content-Type text/plain;
    }

    # API endpoints
    location / {
        # API Key authentication (opcjonalne)
EOF

# Dodaj sprawdzanie API key jesli podany
if [ -n "$API_KEY" ]; then
    cat >> /etc/nginx/sites-available/freeai4me << EOF
        # Sprawdz API key
        if (\$http_authorization != "Bearer $API_KEY") {
            return 401 '{"error": "Unauthorized"}';
        }

EOF
fi

cat >> /etc/nginx/sites-available/freeai4me << EOF
        proxy_pass http://lm_studio;
        proxy_http_version 1.1;

        # Headers
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;

        # WebSocket support (dla streaming)
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";

        # Timeouts (wazne dla duzych promptow)
        proxy_connect_timeout 60s;
        proxy_send_timeout 300s;
        proxy_read_timeout 300s;

        # Buffering off dla streaming
        proxy_buffering off;
        proxy_cache off;

        # CORS
        add_header Access-Control-Allow-Origin * always;
        add_header Access-Control-Allow-Methods "GET, POST, OPTIONS" always;
        add_header Access-Control-Allow-Headers "Authorization, Content-Type" always;

        if (\$request_method = OPTIONS) {
            return 204;
        }
    }
}
EOF

# Aktywuj konfiguracje
ln -sf /etc/nginx/sites-available/freeai4me /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test konfiguracji
nginx -t

systemctl reload nginx

# 5. SSL certyfikat
echo -e "${YELLOW}[5/5] Generowanie certyfikatu SSL...${NC}"
certbot --nginx -d $DOMAIN --non-interactive --agree-tos --register-unsafely-without-email || {
    echo -e "${YELLOW}Certbot nie mogl automatycznie skonfigurowac SSL.${NC}"
    echo -e "${YELLOW}Uruchom rucznie: certbot --nginx -d $DOMAIN${NC}"
}

# Firewall
echo -e "${YELLOW}[Bonus] Konfiguracja firewall...${NC}"
if command -v ufw &> /dev/null; then
    ufw allow 22/tcp
    ufw allow 80/tcp
    ufw allow 443/tcp
    ufw --force enable
    echo -e "${GREEN}UFW skonfigurowany${NC}"
fi

# Podsumowanie
echo ""
echo -e "${CYAN}============================================${NC}"
echo -e "${GREEN}   Setup zakonczony!${NC}"
echo -e "${CYAN}============================================${NC}"
echo ""
echo -e "${CYAN}Nastepne kroki:${NC}"
echo ""
echo -e "1. Na Windows PC uruchom tunel:"
echo -e "   ${GREEN}.\start-tunnel.ps1 -VpsHost $DOMAIN${NC}"
echo ""
echo -e "2. API bedzie dostepne pod:"
echo -e "   ${GREEN}https://$DOMAIN/v1${NC}"
echo ""
echo -e "3. Sprawdz status tunelu:"
echo -e "   ${GREEN}curl https://$DOMAIN/v1/models${NC}"
echo ""

if [ -n "$API_KEY" ]; then
    echo -e "4. API Key (dodaj do Authorization header):"
    echo -e "   ${GREEN}Bearer $API_KEY${NC}"
    echo ""
fi

echo -e "${CYAN}Przydatne komendy:${NC}"
echo -e "  Logi nginx:     ${YELLOW}tail -f /var/log/nginx/freeai4me.*.log${NC}"
echo -e "  Status nginx:   ${YELLOW}systemctl status nginx${NC}"
echo -e "  Restart nginx:  ${YELLOW}systemctl restart nginx${NC}"
echo -e "  Polaczenia SSH: ${YELLOW}ss -tuln | grep $TUNNEL_PORT${NC}"
