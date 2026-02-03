#!/usr/bin/env python3
"""
FreeAi4Me - Python Client Example
Uzywa OpenAI SDK do komunikacji z lokalnym AI przez VPS.
"""

from openai import OpenAI

# Konfiguracja - zmien na swoja domene!
BASE_URL = "https://twoja-domena.pl/v1"
API_KEY = "local-key"  # Jesli ustawiles API key w nginx, wpisz go tutaj


def create_client():
    """Tworzy klienta OpenAI skierowanego na twoje API."""
    return OpenAI(
        base_url=BASE_URL,
        api_key=API_KEY,
    )


def list_models():
    """Listuje dostepne modele."""
    client = create_client()
    models = client.models.list()
    print("Dostepne modele:")
    for model in models.data:
        print(f"  - {model.id}")
    return models


def chat_completion(message: str, stream: bool = False):
    """
    Wysyla wiadomosc do modelu i zwraca odpowiedz.

    Args:
        message: Wiadomosc do wyslania
        stream: Czy uzywac streaming (odpowiedz token po tokenie)
    """
    client = create_client()

    messages = [
        {"role": "system", "content": "Jestes pomocnym asystentem AI."},
        {"role": "user", "content": message}
    ]

    if stream:
        # Streaming - odpowiedz token po tokenie
        print("Odpowiedz: ", end="", flush=True)
        response = client.chat.completions.create(
            model="local-model",  # LM Studio ignoruje nazwe modelu
            messages=messages,
            stream=True,
            max_tokens=1000,
            temperature=0.7,
        )

        full_response = ""
        for chunk in response:
            if chunk.choices[0].delta.content:
                content = chunk.choices[0].delta.content
                print(content, end="", flush=True)
                full_response += content
        print()  # Nowa linia na koniec
        return full_response
    else:
        # Bez streaming - cala odpowiedz na raz
        response = client.chat.completions.create(
            model="local-model",
            messages=messages,
            max_tokens=1000,
            temperature=0.7,
        )

        content = response.choices[0].message.content
        print(f"Odpowiedz: {content}")
        return content


def chat_conversation():
    """Interaktywna rozmowa z modelem."""
    client = create_client()
    messages = [
        {"role": "system", "content": "Jestes pomocnym asystentem AI. Odpowiadaj zwiezle po polsku."}
    ]

    print("=== FreeAi4Me Chat ===")
    print("Wpisz 'quit' aby zakonczyc")
    print()

    while True:
        user_input = input("Ty: ").strip()

        if user_input.lower() in ['quit', 'exit', 'q']:
            print("Do zobaczenia!")
            break

        if not user_input:
            continue

        messages.append({"role": "user", "content": user_input})

        print("AI: ", end="", flush=True)

        response = client.chat.completions.create(
            model="local-model",
            messages=messages,
            stream=True,
            max_tokens=500,
            temperature=0.7,
        )

        assistant_message = ""
        for chunk in response:
            if chunk.choices[0].delta.content:
                content = chunk.choices[0].delta.content
                print(content, end="", flush=True)
                assistant_message += content

        print()  # Nowa linia
        messages.append({"role": "assistant", "content": assistant_message})


def code_completion(code: str, instruction: str):
    """Pomoc z kodem."""
    client = create_client()

    prompt = f"""Instruction: {instruction}

Code:
```
{code}
```

Please help with the above code."""

    response = client.chat.completions.create(
        model="local-model",
        messages=[
            {"role": "system", "content": "You are a helpful coding assistant. Provide clear, concise code solutions."},
            {"role": "user", "content": prompt}
        ],
        max_tokens=1500,
        temperature=0.3,  # Nizsza temperatura dla kodu
    )

    return response.choices[0].message.content


# ============================================
# Przyklady uzycia
# ============================================

if __name__ == "__main__":
    import sys

    print("=" * 50)
    print("FreeAi4Me - Python Client Example")
    print("=" * 50)
    print()

    # Sprawdz polaczenie
    print("1. Sprawdzam polaczenie z API...")
    try:
        models = list_models()
        print("   OK! Polaczenie dziala.\n")
    except Exception as e:
        print(f"   BLAD: {e}")
        print("   Upewnij sie ze tunel SSH dziala i LM Studio jest uruchomione.")
        sys.exit(1)

    # Prosty chat
    print("2. Test prostego chatu...")
    chat_completion("Powiedz mi cos ciekawego o Polsce w 2 zdaniach.")
    print()

    # Streaming
    print("3. Test streaming...")
    chat_completion("Napisz krotki wiersz o programowaniu.", stream=True)
    print()

    # Interaktywna rozmowa
    print("4. Tryb interaktywny")
    print("-" * 50)
    chat_conversation()
