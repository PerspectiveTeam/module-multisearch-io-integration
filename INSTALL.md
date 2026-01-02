# Інструкції з встановлення

## Встановлення через Composer

Виконайте наступну команду для встановлення модуля:

```bash
composer require perspectiveteam/module-multisearch-io-integration:*
```

## Активація модуля

Після встановлення пакета, необхідно активувати модуль та виконати оновлення бази даних:

```bash
bin/magento module:enable Perspective_MultisearchIo
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
```

## Сторінка пошуку

Сторінка результатів пошуку доступна за адресою:
`https://your.domain/multisearch/search`

## Web API

Модуль надає наступні ендпоінти для інтеграції:

### Отримання результатів автозаповнення

- **URL:** `/V1/multisearchio/search/`
- **Method:** `GET`
- **Service Class:** `Perspective\MultisearchIo\Api\AutocompleteSearchInterface`
- **Service Method:** `getAutocomplete`
- **Resources:** `anonymous`

### Видалення з історії пошуку

- **URL:** `/V1/multisearchio/search/`
- **Method:** `DELETE`
- **Service Class:** `Perspective\MultisearchIo\Api\AutocompleteSearchInterface`
- **Service Method:** `deleteFromHistory`
- **Resources:** `anonymous`

