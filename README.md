## Описание
Это пример back-end кода на Symfony 4.4.</br></br>
При разработке была использована MySQL (миграции ориентированы на кодировку utf8mb4).</br></br>
Создан контроллер по типу JSON REST API со следующими роутами :
- HTTP-метод : GET :
  - URI : /api/products | возвращает все имеющиеся products
  - URI : /api/product/{id} | возвращает product по его id</br></br>
- HTTP-метод : POST :
  - URI : /api/products | создаёт products по спецификациям, указанным в JSON-запросе
  - URI : /api/product | создаёт product по спецификации, указанной в JSON-запросе</br></br>
- HTTP-метод : PUT | URI : /api/product/{id} | обновляет product с указанным id в соответствии со спецификацией, указанной в JSON-запросе
- HTTP-метод : DELETE | URI : /api/product/{id} | удаляет product с указанным id</br></br>

Созданы две сущности (entities) :
- category :
  - id (int, autoincrement)
  - title (string, length 3-12)
  - eId (int|null, unique)
- product :
  - id (int, autoincrement)
  - title (string, length 3-12)
  - price (float, range 0-200)
  - eId (int|null, unique)
  - categories (связь ManyToMany с сущностью category)</br></br>

Создан функциональный тест, подтверждающий правильность функционирования роутов контроллера, 
перечисленных выше (`/tests/Controller/ProductManagerControllerTest.php`).</br></br>
Из-за недостатка времени в архитектуре возможны небольшие недочёты, незначительные на данном этапе (например, могут быть не созданы некоторые интерфейсы). 
По той же причине в коде нет подробных комментариев.
## Развёртывание проекта
Получив файлы из репозитория, установить зависимости проекта через Composer.</br></br>
Для корректной работы нужно вписать в файл `/.env.local`
параметры подключения к базе данных по типу : `DATABASE_URL="mysql://LOGIN:PASSWORD@127.0.0.1:3306/DB_NAME?serverVersion=5.7&charset=utf8mb4"`.</br>
Для тестирования этот параметр можно переопределить в файле `/.env.test.local`.</br></br>
Для запуска тестов - команда `php bin/phpunit`.