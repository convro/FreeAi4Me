# FreeAi4Me 🚀

**Darmowe AI API na twojej karcie graficznej** - OpenAI-compatible API hostowane lokalnie z tunelem przez VPS.

## Architektura

```
[Twoje aplikacje / kod]
         ↓ HTTPS
[VPS + domena + nginx + SSL]
         ↓ reverse SSH tunnel
[Windows PC + RTX 3060 Ti + LM Studio]
```

## Wymagania

### Windows PC (z GPU)
- Windows 10/11
- RTX 3060 Ti (8GB VRAM) lub lepsza
- [LM Studio](https://lmstudio.ai/) zainstalowane
- Git Bash lub OpenSSH

### VPS
- Linux (Ubuntu/Debian)
- Nginx
- Certbot (Let's Encrypt)
- Domena wskazująca na IP VPS

### Mac (opcjonalnie)
- Do zarządzania przez SSH

## Szybki Start

### 1. Setup Windows PC

```powershell
# Pobierz i zainstaluj LM Studio z https://lmstudio.ai/
# Pobierz model (np. Mistral 7B Q4_K_M)

# Uruchom skrypt tunelu
.\windows\start-tunnel.ps1 -VpsHost "twoja-domena.pl" -VpsUser "root"
```

### 2. Setup VPS

```bash
# Na VPS
sudo bash vps/setup.sh twoja-domena.pl
```

### 3. Użyj API

```python
from openai import OpenAI

client = OpenAI(
    base_url="https://twoja-domena.pl/v1",
    api_key="local-key"  # jakikolwiek string
)

response = client.chat.completions.create(
    model="local-model",
    messages=[{"role": "user", "content": "Cześć!"}]
)
print(response.choices[0].message.content)
```

## Polecane Modele dla RTX 3060 Ti (8GB VRAM)

| Model | Quantization | VRAM | Jakość |
|-------|-------------|------|--------|
| Mistral 7B | Q4_K_M | ~5GB | ⭐⭐⭐⭐⭐ |
| Llama 3 8B | Q4_K_M | ~6GB | ⭐⭐⭐⭐⭐ |
| Phi-3 Mini 3.8B | Q8 | ~4GB | ⭐⭐⭐⭐ |
| Qwen 2 7B | Q4_K_M | ~5GB | ⭐⭐⭐⭐ |
| CodeLlama 7B | Q4_K_M | ~5GB | ⭐⭐⭐⭐ (kod) |

## Struktura Projektu

```
FreeAi4Me/
├── windows/           # Skrypty dla Windows PC
│   ├── start-tunnel.ps1
│   ├── start-tunnel.bat
│   └── install-ssh.ps1
├── vps/               # Skrypty i config dla VPS
│   ├── setup.sh
│   └── nginx.conf.template
├── client/            # Przykłady klienta
│   ├── python_example.py
│   └── curl_example.sh
└── docs/              # Dokumentacja
    └── TROUBLESHOOTING.md
```

## Bezpieczeństwo

- Tunel SSH jest szyfrowany
- HTTPS na VPS (Let's Encrypt)
- Opcjonalny API key w nginx
- Firewall na VPS

## Troubleshooting

Zobacz [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md)

## License

MIT
