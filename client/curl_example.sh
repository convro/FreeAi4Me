#!/bin/bash
# FreeAi4Me - Przyklady curl
# Zmien DOMAIN na swoja domene!

DOMAIN="twoja-domena.pl"
API_KEY="local-key"  # Jesli ustawiles API key

echo "============================================"
echo "   FreeAi4Me - curl Examples"
echo "============================================"
echo ""

# 1. Health check
echo "1. Health check..."
curl -s "https://$DOMAIN/health"
echo ""
echo ""

# 2. Lista modeli
echo "2. Lista modeli..."
curl -s "https://$DOMAIN/v1/models" \
  -H "Authorization: Bearer $API_KEY" | jq .
echo ""

# 3. Prosty chat (bez streaming)
echo "3. Chat completion (bez streaming)..."
curl -s "https://$DOMAIN/v1/chat/completions" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $API_KEY" \
  -d '{
    "model": "local-model",
    "messages": [
      {"role": "user", "content": "Powiedz czesc po polsku!"}
    ],
    "max_tokens": 100,
    "temperature": 0.7
  }' | jq .
echo ""

# 4. Chat ze streaming
echo "4. Chat completion (streaming)..."
curl -s "https://$DOMAIN/v1/chat/completions" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $API_KEY" \
  -d '{
    "model": "local-model",
    "messages": [
      {"role": "user", "content": "Napisz 3 fakty o kosmosie."}
    ],
    "max_tokens": 300,
    "temperature": 0.7,
    "stream": true
  }'
echo ""
echo ""

# 5. Embedding (jesli model obsluguje)
echo "5. Embeddings (opcjonalne - wymaga modelu embedding)..."
curl -s "https://$DOMAIN/v1/embeddings" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $API_KEY" \
  -d '{
    "model": "local-model",
    "input": "Hello world"
  }' | jq '.data[0].embedding[:5]' 2>/dev/null || echo "   (Model nie obsluguje embeddings)"
echo ""

echo "============================================"
echo "   Gotowe!"
echo "============================================"
