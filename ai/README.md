# AI-агенты проекта (Ollama)

Агенты работают с локальной моделью через Ollama. Код не зависит от конкретной модели:
провайдер реализует интерфейс `App\Ai\LlmProvider`, а агент работает только с ним.

## Структура

```
ai/
  bin/agent.php              CLI-точка входа
  src/LlmProvider.php        контракт провайдера LLM
  src/OllamaProvider.php     провайдер Ollama (/api/chat)
  src/Agent.php              цикл агента: модель → инструменты → ответ
  src/Tool.php               контракт инструмента
  src/Tools/                 инструменты (чтение и листинг файлов проекта)
  tests/run.php              тесты без внешних зависимостей
  tests/stub-ollama.php      заглушка протокола Ollama для проверки без модели
```

## Запуск

1. Поднять Ollama и скачать модель:

   ```bash
   docker compose up -d ollama
   docker exec -it eidoc_ollama ollama pull qwen2.5:1.5b
   ```

   Для агентов нужна модель с поддержкой tool calling, например `qwen2.5`, `llama3.1`, `mistral-nemo`.
   Другую модель можно выбрать через `OLLAMA_MODEL=<имя>` в окружении или `.env` рядом с `docker-compose.yml`.

2. Запустить агента:

   ```bash
   docker compose run --rm agent "Какие сервисы описаны в docker-compose.yml?"
   ```

   Без Docker, если Ollama доступна на хосте:

   ```bash
   OLLAMA_BASE_URL=http://127.0.0.1:11434 php ai/bin/agent.php "Что в README?"
   ```

3. Тесты:

   ```bash
   php ai/tests/run.php
   ```

## Безопасность инструментов

- Инструменты только читают файлы. Записи и запуска команд нет.
- Пути ограничены корнем проекта: `..`, абсолютные пути и симлинки наружу отклоняются.
- Каталоги `.git`, `node_modules`, `vendor` не листаются, размер читаемого файла ограничен 20 КБ.
- Ошибки инструментов возвращаются модели как результат, а не роняют агента.
- Число шагов цикла ограничено (`maxSteps`, по умолчанию 6).

## Добавление инструмента

Реализуйте `App\Ai\Tool` (`name()`, `definition()`, `execute(array $arguments): string`)
и передайте экземпляр в `Agent` в `ai/bin/agent.php`.
