# Troubleshooting Guide

## Najczestsze problemy i rozwiazania

### 1. Tunel SSH nie dziala

**Objawy:**
- `curl https://domena.pl/v1/models` zwraca blad 502 lub timeout

**Rozwiazania:**

```bash
# Na VPS - sprawdz czy port tunelu jest otwarty
ss -tuln | grep 8080

# Jesli brak - tunel nie jest polaczony
# Na Windows sprawdz czy skrypt tunelu dziala
```

```powershell
# Na Windows - sprawdz polaczenie
Test-NetConnection -ComputerName twoja-domena.pl -Port 22
```

### 2. LM Studio nie odpowiada

**Objawy:**
- Tunel dziala, ale API zwraca bledy

**Rozwiazania:**

1. Sprawdz czy LM Studio jest uruchomione
2. Sprawdz czy model jest zaladowany
3. Sprawdz czy "Local Server" jest wlaczony w LM Studio
4. Sprawdz port (domyslnie 1234):

```powershell
# Na Windows
curl http://localhost:1234/v1/models
```

### 3. Blad SSL / certyfikat

**Objawy:**
- `SSL certificate problem` w curl
- Przegladarka pokazuje ostrzezenie o certyfikacie

**Rozwiazania:**

```bash
# Na VPS - odnow certyfikat
sudo certbot renew

# Sprawdz czy certyfikat istnieje
sudo ls -la /etc/letsencrypt/live/twoja-domena.pl/

# Jesli brak - wygeneruj nowy
sudo certbot --nginx -d twoja-domena.pl
```

### 4. Timeout przy dlugich odpowiedziach

**Objawy:**
- Krotkie prompty dzialaja, dlugie generacje timeout

**Rozwiazania:**

Zwieksz timeout w nginx (`/etc/nginx/sites-available/freeai4me`):

```nginx
proxy_connect_timeout 120s;
proxy_send_timeout 600s;
proxy_read_timeout 600s;
```

```bash
sudo nginx -t && sudo systemctl reload nginx
```

### 5. Blad 401 Unauthorized

**Objawy:**
- API zwraca `{"error": "Unauthorized"}`

**Rozwiazania:**

1. Sprawdz czy API key jest poprawny:

```bash
curl https://domena.pl/v1/models -H "Authorization: Bearer twoj-klucz"
```

2. Jesli nie uzywasz API key, sprawdz config nginx - moze byc wlaczony

### 6. Model dziala wolno

**Objawy:**
- Generowanie trwa bardzo dlugo

**Rozwiazania:**

1. Sprawdz uzycie GPU w Task Manager
2. Uzyj mniejszej kwantyzacji (Q4 zamiast Q8)
3. Zmniejsz `max_tokens`
4. Sprawdz czy inne programy nie uzywaja GPU

### 7. Out of Memory (VRAM)

**Objawy:**
- LM Studio crashuje przy ladowaniu modelu
- Blad "CUDA out of memory"

**Rozwiazania:**

1. Uzyj mniejszego modelu:
   - 7B zamiast 13B
   - Q4_K_M zamiast Q8
2. Zamknij inne programy uzywajace GPU
3. W LM Studio zmniejsz "GPU layers"

### 8. SSH: Connection refused

**Objawy:**
- `ssh: connect to host ... port 22: Connection refused`

**Rozwiazania:**

```bash
# Na VPS sprawdz czy SSH dziala
sudo systemctl status sshd

# Sprawdz firewall
sudo ufw status
sudo ufw allow 22/tcp
```

### 9. SSH: Permission denied

**Objawy:**
- `Permission denied (publickey)`

**Rozwiazania:**

```powershell
# Na Windows - sprawdz klucz
cat ~/.ssh/id_rsa.pub

# Skopiuj na VPS
# Na VPS:
echo "TWOJ_KLUCZ_PUBLICZNY" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

### 10. nginx: Bad Gateway (502)

**Objawy:**
- Strona pokazuje "502 Bad Gateway"

**Rozwiazania:**

```bash
# Sprawdz logi nginx
sudo tail -f /var/log/nginx/freeai4me.error.log

# Sprawdz czy tunel jest aktywny
ss -tuln | grep 8080

# Sprawdz config nginx
sudo nginx -t
```

---

## Diagnostyka

### Sprawdz caly flow:

```bash
# 1. Na Windows - czy LM Studio dziala?
curl http://localhost:1234/v1/models

# 2. Na VPS - czy tunel jest polaczony?
ss -tuln | grep 8080

# 3. Na VPS - czy nginx dziala?
sudo systemctl status nginx

# 4. Z zewnatrz - czy API jest dostepne?
curl https://twoja-domena.pl/health
curl https://twoja-domena.pl/v1/models
```

### Logi do analizy:

```bash
# nginx access log
sudo tail -f /var/log/nginx/freeai4me.access.log

# nginx error log
sudo tail -f /var/log/nginx/freeai4me.error.log

# SSH daemon log
sudo journalctl -u sshd -f
```

---

## Kontakt

Jesli problem nie zostal rozwiazany, sprawdz:
1. GitHub Issues tego projektu
2. Dokumentacje LM Studio: https://lmstudio.ai/docs
3. Dokumentacje nginx: https://nginx.org/en/docs/
