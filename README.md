# 🧪 Aula Prática: Testes Automatizados e TDD no Laravel com Pest

Este projeto Laravel é a base para uma **dinâmica prática de Testes Automatizados e TDD (Test-Driven Development)** utilizando o framework **Pest** e banco de dados **SQLite**.

> **Nota sobre este repositório:** O projeto já vem configurado com **Laravel**, **SQLite** e **Pest** (incluindo o plugin de testes de navegador) no `composer.json`, pronto para a aula. Mesmo assim, todos os passos de instalação e configuração do zero estão documentados abaixo para referência e prática completa.

---

## 📋 Sumário da Dinâmica

1. [Pré-requisitos](#-1-pré-requisitos)
2. [Instalação e Configuração do Projeto (Laravel + SQLite)](#-2-instalação-e-configuração-do-projeto-laravel--sqlite)
3. [Instalação e Configuração do Pest](#-3-instalação-e-configuração-do-pest)
4. [Prática 1 — TDD: Persistir um Produto (Teste → Model → Migration → Controller → Rota)](#-4-prática-1--tdd-persistir-um-produto)
5. [Prática 2 — Validações Separadas (Form Request) com TDD](#-5-prática-2--validações-separadas-form-request-com-tdd)
6. [Prática 3 — Teste de Integração com Rota API (JSON) + Postman](#-6-prática-3--teste-de-integração-com-rota-api-json--postman)
7. [Prática 4 — Teste de Integração de Consulta de CEP (Camada Service)](#-7-prática-4--teste-de-integração-de-consulta-de-cep-camada-service)
8. [Prática 5 — Teste de Regra de Negócio: Cálculo de Desconto (Service)](#-8-prática-5--teste-de-regra-de-negócio-cálculo-de-desconto-service)
9. [Prática 6 — Teste Automatizado no Navegador (Pest Browser Testing) com Tela de CEP](#-9-prática-6--teste-automatizado-no-navegador-pest-browser-testing-com-tela-de-cep)

---

## 🔧 1. Pré-requisitos

Certifique-se de ter instalado em sua máquina:

- **PHP 8.3+** (com extensões `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `curl`)
- **Composer**
- **Node.js 20+ e NPM** (necessário para o Playwright na Prática 6)
- **Postman** (ou Insomnia) para testar a rota de API na Prática 3

Verifique no terminal:

```bash
php -v
composer -V
node -v
npm -v
```

---

## 🚀 2. Instalação e Configuração do Projeto (Laravel + SQLite)

### Caso esteja clonando este repositório base

```bash
# 1. Clone o repositório e acesse a pasta do projeto
git clone https://github.com/joseeverton/pratica-testes-laravel-pest.git
cd pratica-testes-laravel-pest

# 2. Instale as dependências PHP (já inclui o Pest)
composer install

# 3. Copie o arquivo de ambiente
cp .env.example .env

# 4. Gere a chave da aplicação
php artisan key:generate

# 5. Crie o arquivo do banco SQLite (se ainda não existir)
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"

# 6. Execute as migrations iniciais
php artisan migrate
```

### Caso esteja criando o projeto Laravel do zero

```bash
# 1. Crie o projeto Laravel
composer create-project laravel/laravel pratica_teste_com_pest
cd pratica_teste_com_pest

# 2. Configure o arquivo .env para usar SQLite
```

No arquivo `.env`, garanta que a seção de banco de dados esteja configurada para **SQLite**:

```env
DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=
```

Crie o arquivo físico do banco e rode as migrations:

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
```

### Banco de dados para os testes (`phpunit.xml`)

Nos testes automatizados, o Laravel utiliza **SQLite em memória** (`:memory:`), deixando os testes muito rápidos e sem sujar o banco de desenvolvimento. Confira no arquivo `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

---

## 🧪 3. Instalação e Configuração do Pest

> Este projeto **já possui o Pest instalado no `composer.json`**. Caso queira instalar o Pest em um projeto Laravel novo do zero, siga os comandos abaixo:

```bash
# 1. Remova o PHPUnit padrão (o Pest gerencia a versão compatível)
composer remove phpunit/phpunit --dev

# 2. Permita o plugin do Pest no Composer
composer config allow-plugins.pestphp/pest-plugin true

# 3. Instale o Pest, o plugin para Laravel e o plugin para testes de navegador
composer require pestphp/pest pestphp/pest-plugin-laravel pestphp/pest-plugin-browser --dev --with-all-dependencies

# 4. Inicialize a estrutura do Pest
./vendor/bin/pest --init
```

### Configurando `tests/Pest.php`

Garanta que `tests/Pest.php` aplique `TestCase` e `RefreshDatabase` nas pastas `Feature` e `Browser`:

```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');
```

### Executando os testes

```bash
# Rodar toda a suíte de testes
./vendor/bin/pest

# Ou usando o comando do Artisan
php artisan test

# Filtrar um teste específico
./vendor/bin/pest --filter=ProdutoTest
```

Se tudo estiver configurado corretamente, os testes iniciais de exemplo devem passar!

---

## 🔴🟢🔵 4. Prática 1 — TDD: Persistir um Produto

Vamos aplicar a metodologia **TDD (Test-Driven Development)** seguindo o ciclo:

1. **RED (Vermelho):** Escrevemos o teste primeiro e executamos — ele **deve falhar**.
2. **GREEN (Verde):** Implementamos o código mínimo necessário (Migration, Model, Controller e Rota) para o teste **passar**.
3. **REFACTOR (Refatorar):** Melhoramos o código mantendo os testes passando.

Nosso produto terá os campos: `id`, `nome` e `preco`.

### Passo 4.1 (RED) — Criar o teste de persistência primeiro

Crie o arquivo `tests/Feature/ProdutoTest.php`:

```bash
php artisan make:test ProdutoTest --pest
```

Edite `tests/Feature/ProdutoTest.php`:

```php
<?php

it('deve persistir um produto no banco de dados', function () {
    $dados = [
        'nome' => 'Notebook Gamer',
        'preco' => 4599.90,
    ];

    $response = $this->post('/produtos', $dados);

    $response->assertStatus(201);

    $this->assertDatabaseHas('produtos', [
        'nome' => 'Notebook Gamer',
        'preco' => 4599.90,
    ]);
});
```

Rode o teste e observe a fase **RED** (vai falhar com status `404` pois a rota ainda não existe):

```bash
./vendor/bin/pest tests/Feature/ProdutoTest.php
```

### Passo 4.2 (GREEN) — Criar Model e Migration

```bash
php artisan make:model Produto -m
```

Edite a migration criada em `database/migrations/xxxx_xx_xx_xxxxxx_create_produtos_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->decimal('preco', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
```

Execute a migration:

```bash
php artisan migrate
```

Edite o Model `app/Models/Produto.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    protected $fillable = [
        'nome',
        'preco',
    ];
}
```

### Passo 4.3 (GREEN) — Criar o Controller

```bash
php artisan make:controller ProdutoController
```

Edite `app/Http/Controllers/ProdutoController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{
    public function store(Request $request)
    {
        $produto = Produto::create($request->only(['nome', 'preco']));

        return response()->json($produto, 201);
    }
}
```

### Passo 4.4 (GREEN) — Registrar a Rota

Em `routes/web.php`, adicione:

```php
use App\Http\Controllers\ProdutoController;
use Illuminate\Support\Facades\Route;

Route::post('/produtos', [ProdutoController::class, 'store']);
```

### Passo 4.5 — Validar o teste (GREEN!)

```bash
./vendor/bin/pest tests/Feature/ProdutoTest.php
```

O teste agora deve passar! ✅

---

## 🛡️ 5. Prática 2 — Validações Separadas (Form Request) com TDD

Agora vamos garantir integridade dos dados testando regras de validação para o cadastro de produtos:
- Campos obrigatórios (não podem estar vazios);
- Quantidade mínima de caracteres para o `nome` (`min:3`);
- Quantidade máxima de caracteres para o `nome` (`max:100`);
- Valor válido para o `preco` (deve ser numérico e maior que zero).

Para manter o Controller limpo e seguindo boas práticas do Laravel, vamos criar as **validações separadas** em um **Form Request**.

### Passo 5.1 (RED) — Adicionar os testes de validação

Adicione os novos testes ao final de `tests/Feature/ProdutoTest.php`:

```php
it('não deve permitir cadastrar produto com campos vazios', function () {
    $response = $this->postJson('/produtos', [
        'nome' => '',
        'preco' => '',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['nome', 'preco']);

    $this->assertDatabaseCount('produtos', 0);
});

it('não deve permitir nome de produto menor que o tamanho mínimo (3 caracteres)', function () {
    $response = $this->postJson('/produtos', [
        'nome' => 'TV',
        'preco' => 1500.00,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['nome']);
});

it('não deve permitir nome de produto maior que o tamanho máximo (100 caracteres)', function () {
    $response = $this->postJson('/produtos', [
        'nome' => str_repeat('A', 101),
        'preco' => 1500.00,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['nome']);
});

it('não deve permitir preço inválido (não numérico, zero ou negativo)', function (mixed $precoInvalido) {
    $response = $this->postJson('/produtos', [
        'nome' => 'Mouse Sem Fio',
        'preco' => $precoInvalido,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['preco']);
})->with([
    'texto' => ['caro'],
    'zero' => [0],
    'negativo' => [-50.00],
]);
```

Rode os testes e veja falhar (RED):

```bash
./vendor/bin/pest tests/Feature/ProdutoTest.php
```

### Passo 5.2 (GREEN) — Criar a classe de validação separada (Form Request)

```bash
php artisan make:request StoreProdutoRequest
```

Edite `app/Http/Requests/StoreProdutoRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'min:3', 'max:100'],
            'preco' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do produto é obrigatório.',
            'nome.min' => 'O nome do produto deve ter no mínimo 3 caracteres.',
            'nome.max' => 'O nome do produto deve ter no máximo 100 caracteres.',
            'preco.required' => 'O preço do produto é obrigatório.',
            'preco.numeric' => 'O preço do produto deve ser um número válido.',
            'preco.gt' => 'O preço do produto deve ser maior que zero.',
        ];
    }
}
```

### Passo 5.3 (GREEN) — Usar o `StoreProdutoRequest` no Controller

Atualize `app/Http/Controllers/ProdutoController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProdutoRequest;
use App\Models\Produto;

class ProdutoController extends Controller
{
    public function store(StoreProdutoRequest $request)
    {
        $produto = Produto::create($request->validated());

        return response()->json($produto, 201);
    }
}
```

Rode os testes novamente (GREEN!):

```bash
./vendor/bin/pest tests/Feature/ProdutoTest.php
```

---

## 🌐 6. Prática 3 — Teste de Integração com Rota API (JSON) + Postman

Agora vamos criar rotas de API (`/api/produtos` e `/api/produtos/{produto}`) para consultar produtos em formato JSON, validar tudo com testes de integração no Pest e testar manualmente no **Postman**.

### Passo 6.1 — Habilitar o arquivo de rotas de API (`routes/api.php`)

Crie o arquivo `routes/api.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

// As rotas da API serão adicionadas aqui
```

Registre `routes/api.php` no arquivo `bootstrap/app.php` dentro de `withRouting(...)`:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

### Passo 6.2 (RED) — Criar o teste de integração da API

```bash
php artisan make:test ProdutoApiTest --pest
```

Edite `tests/Feature/ProdutoApiTest.php`:

```php
<?php

use App\Models\Produto;

it('deve listar os produtos em formato JSON pela API', function () {
    Produto::create(['nome' => 'Teclado Mecânico', 'preco' => 350.00]);
    Produto::create(['nome' => 'Monitor 27 Polegadas', 'preco' => 1299.90]);

    $response = $this->getJson('/api/produtos');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'nome', 'preco'],
            ],
        ]);
});

it('deve consultar um produto específico por ID em formato JSON', function () {
    $produto = Produto::create([
        'nome' => 'Headset Bluetooth',
        'preco' => 280.50,
    ]);

    $response = $this->getJson("/api/produtos/{$produto->id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $produto->id,
                'nome' => 'Headset Bluetooth',
                'preco' => 280.50,
            ],
        ]);
});

it('deve retornar 404 ao consultar um produto inexistente na API', function () {
    $response = $this->getJson('/api/produtos/9999');

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Produto não encontrado.',
        ]);
});
```

Rode o teste (RED):

```bash
./vendor/bin/pest tests/Feature/ProdutoApiTest.php
```

### Passo 6.3 (GREEN) — Criar Controller da API e Rotas

```bash
php artisan make:controller Api/ProdutoApiController
```

Edite `app/Http/Controllers/Api/ProdutoApiController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProdutoRequest;
use App\Models\Produto;
use Illuminate\Http\JsonResponse;

class ProdutoApiController extends Controller
{
    public function index(): JsonResponse
    {
        $produtos = Produto::all();

        return response()->json([
            'data' => $produtos,
        ], 200);
    }

    public function show(int $id): JsonResponse
    {
        $produto = Produto::find($id);

        if (! $produto) {
            return response()->json([
                'message' => 'Produto não encontrado.',
            ], 404);
        }

        return response()->json([
            'data' => $produto,
        ], 200);
    }

    public function store(StoreProdutoRequest $request): JsonResponse
    {
        $produto = Produto::create($request->validated());

        return response()->json([
            'data' => $produto,
        ], 201);
    }
}
```

Registre as rotas em `routes/api.php`:

```php
<?php

use App\Http\Controllers\Api\ProdutoApiController;
use Illuminate\Support\Facades\Route;

Route::get('/produtos', [ProdutoApiController::class, 'index']);
Route::get('/produtos/{id}', [ProdutoApiController::class, 'show']);
Route::post('/produtos', [ProdutoApiController::class, 'store']);
```

Rode os testes (GREEN!):

```bash
./vendor/bin/pest tests/Feature/ProdutoApiTest.php
```

### Passo 6.4 — Testando a API no Postman

1. **Suba o servidor local do Laravel:**
   ```bash
   php artisan serve
   ```
   O servidor ficará disponível em `http://127.0.0.1:8000`.

2. **Abra o Postman** e clique em **New → Collection** (nomeie como `Aula Prática Laravel + Pest`).

3. **Crie uma requisição para cadastrar um produto (`POST`):**
   - Clique em **Add Request** e escolha o método `POST`.
   - URL: `http://127.0.0.1:8000/api/produtos`
   - Aba **Headers**: adicione `Accept` com valor `application/json` e `Content-Type` com valor `application/json`.
   - Aba **Body** → selecione **raw** → **JSON** e informe:
     ```json
     {
       "nome": "Webcam Full HD",
       "preco": 249.90
     }
     ```
   - Clique em **Send** e confira o retorno `201 Created`.

4. **Crie uma requisição para listar produtos (`GET`):**
   - Método: `GET`
   - URL: `http://127.0.0.1:8000/api/produtos`
   - Aba **Headers**: `Accept: application/json`
   - Clique em **Send** e observe a lista em JSON com status `200 OK`.

5. **Crie uma requisição para consultar um produto por ID (`GET`):**
   - Método: `GET`
   - URL: `http://127.0.0.1:8000/api/produtos/1`
   - Clique em **Send** e confira o JSON do produto (ou teste com `/999` para ver o `404 Not Found`).

---

## 📍 7. Prática 4 — Teste de Integração de Consulta de CEP (Camada Service)

Nesta prática, vamos demonstrar como isolar integrações externas em uma camada **Service** (`app/Services/CepService.php`) consultando a API pública do **ViaCEP**, e testar os 3 cenários usando `Http::fake()` do Laravel:

1. **CEP válido** → retorna os dados do endereço;
2. **CEP inexistente** → ViaCEP retorna `{"erro": true}` → tratamos adequadamente;
3. **Serviço indisponível** → API fora do ar (HTTP 500 ou erro de conexão) → tratamos adequadamente.

### Passo 7.1 (RED) — Criar o teste do `CepService`

```bash
php artisan make:test CepServiceTest --pest
```

Edite `tests/Feature/CepServiceTest.php`:

```php
<?php

use App\Services\CepService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

it('deve retornar o endereço para um CEP válido', function () {
    Http::fake([
        'viacep.com.br/ws/50030230/json/' => Http::response([
            'cep' => '50030-230',
            'logradouro' => 'Cais do Apolo',
            'bairro' => 'Recife',
            'localidade' => 'Recife',
            'uf' => 'PE',
        ], 200),
    ]);

    $service = new CepService();
    $resultado = $service->consultar('50030-230');

    expect($resultado)
        ->toBeArray()
        ->and($resultado['logradouro'])->toBe('Cais do Apolo')
        ->and($resultado['localidade'])->toBe('Recife')
        ->and($resultado['uf'])->toBe('PE');
});

it('deve retornar null quando o CEP for inexistente', function () {
    Http::fake([
        'viacep.com.br/ws/99999999/json/' => Http::response([
            'erro' => true,
        ], 200),
    ]);

    $service = new CepService();
    $resultado = $service->consultar('99999-999');

    expect($resultado)->toBeNull();
});

it('deve lançar exceção quando o serviço de CEP estiver indisponível (HTTP 500)', function () {
    Http::fake([
        'viacep.com.br/ws/50030230/json/' => Http::response([], 500),
    ]);

    $service = new CepService();

    expect(fn () => $service->consultar('50030230'))
        ->toThrow(RuntimeException::class, 'Serviço de consulta de CEP indisponível no momento.');
});

it('deve lançar exceção quando houver falha de conexão com o serviço de CEP', function () {
    Http::fake([
        'viacep.com.br/*' => fn () => throw new ConnectionException('Timeout'),
    ]);

    $service = new CepService();

    expect(fn () => $service->consultar('50030230'))
        ->toThrow(RuntimeException::class, 'Serviço de consulta de CEP indisponível no momento.');
});
```

Rode o teste (RED):

```bash
./vendor/bin/pest tests/Feature/CepServiceTest.php
```

### Passo 7.2 (GREEN) — Criar `app/Services/CepService.php`

Crie o diretório `app/Services` e o arquivo `app/Services/CepService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CepService
{
    public function consultar(string $cep): ?array
    {
        $cepLimpo = preg_replace('/\D/', '', $cep);

        if (strlen($cepLimpo) !== 8) {
            return null;
        }

        try {
            $response = Http::timeout(5)->get("https://viacep.com.br/ws/{$cepLimpo}/json/");
        } catch (ConnectionException $e) {
            throw new RuntimeException('Serviço de consulta de CEP indisponível no momento.', 503, $e);
        }

        if ($response->serverError() || $response->failed()) {
            throw new RuntimeException('Serviço de consulta de CEP indisponível no momento.', 503);
        }

        $dados = $response->json();

        if (! is_array($dados) || ($dados['erro'] ?? false) === true) {
            return null;
        }

        return [
            'cep' => $dados['cep'] ?? $cepLimpo,
            'logradouro' => $dados['logradouro'] ?? '',
            'bairro' => $dados['bairro'] ?? '',
            'localidade' => $dados['localidade'] ?? '',
            'uf' => $dados['uf'] ?? '',
        ];
    }
}
```

Rode os testes novamente (GREEN!):

```bash
./vendor/bin/pest tests/Feature/CepServiceTest.php
```

---

## 🏷️ 8. Prática 5 — Teste de Regra de Negócio: Cálculo de Desconto (Service)

Vamos criar outro Service (`app/Services/DescontoService.php`) para encapsular regras de cálculo de desconto — um excelente exemplo de **teste unitário puro** com Pest.

### Passo 8.1 (RED) — Criar o teste unitário de desconto

```bash
php artisan make:test DescontoServiceTest --unit --pest
```

Edite `tests/Unit/DescontoServiceTest.php`:

```php
<?php

use App\Services\DescontoService;

it('deve aplicar desconto percentual corretamente', function (float $preco, float $percentual, float $esperado) {
    $service = new DescontoService();

    $valorFinal = $service->aplicarDesconto($preco, $percentual);

    expect($valorFinal)->toBe($esperado);
})->with([
    '10% sobre 100' => [100.00, 10, 90.00],
    '25% sobre 200' => [200.00, 25, 150.00],
    '0% mantém o valor' => [150.00, 0, 150.00],
    '100% zera o valor' => [80.00, 100, 0.00],
]);

it('deve aplicar desconto por cupom válido', function (string $cupom, float $esperado) {
    $service = new DescontoService();

    expect($service->aplicarCupom(200.00, $cupom))->toBe($esperado);
})->with([
    'DESCONTO10 (10%)' => ['DESCONTO10', 180.00],
    'DESCONTO20 (20%)' => ['DESCONTO20', 160.00],
    'Cupom inexistente (0%)' => ['INVALIDO', 200.00],
]);

it('não deve aceitar percentual de desconto negativo ou acima de 100%', function (float $percentualInvalido) {
    $service = new DescontoService();

    expect(fn () => $service->aplicarDesconto(100.00, $percentualInvalido))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'percentual negativo' => [-5],
    'percentual maior que 100' => [105],
]);

it('não deve aceitar preço negativo', function () {
    $service = new DescontoService();

    expect(fn () => $service->aplicarDesconto(-10.00, 10))
        ->toThrow(InvalidArgumentException::class, 'O preço não pode ser negativo.');
});
```

Rode o teste (RED):

```bash
./vendor/bin/pest tests/Unit/DescontoServiceTest.php
```

### Passo 8.2 (GREEN) — Criar `app/Services/DescontoService.php`

```php
<?php

namespace App\Services;

use InvalidArgumentException;

class DescontoService
{
    private const CUPONS = [
        'DESCONTO10' => 10.0,
        'DESCONTO20' => 20.0,
    ];

    public function aplicarDesconto(float $preco, float $percentual): float
    {
        if ($preco < 0) {
            throw new InvalidArgumentException('O preço não pode ser negativo.');
        }

        if ($percentual < 0 || $percentual > 100) {
            throw new InvalidArgumentException('O percentual de desconto deve estar entre 0 e 100.');
        }

        $valorComDesconto = $preco - ($preco * ($percentual / 100));

        return round($valorComDesconto, 2);
    }

    public function aplicarCupom(float $preco, string $cupom): float
    {
        $percentual = self::CUPONS[strtoupper(trim($cupom))] ?? 0.0;

        return $this->aplicarDesconto($preco, $percentual);
    }
}
```

Rode os testes (GREEN!):

```bash
./vendor/bin/pest tests/Unit/DescontoServiceTest.php
```

---

## 🌐🖥️ 9. Prática 6 — Teste Automatizado no Navegador (Pest Browser Testing) com Tela de CEP

O Pest possui suporte oficial a **testes automatizados no navegador** por meio do pacote `pestphp/pest-plugin-browser` (integrado ao Playwright). Com ele, o Pest abre um navegador real, preenche campos, clica em botões e valida o que aparece na tela!

Vamos criar uma **tela simples e bem organizada de Consulta de CEP** e automatizar **X requisições consecutivas no navegador**.

### Passo 9.1 — Instalar o Playwright (motor do navegador)

O pacote PHP `pestphp/pest-plugin-browser` já está instalado no `composer.json`. Agora instale o Playwright via NPM (apenas uma vez por máquina):

```bash
npm install playwright@latest --save-dev
npx playwright install chromium
```

### Passo 9.2 — Criar o Controller de Consulta de CEP

```bash
php artisan make:controller CepController
```

Edite `app/Http/Controllers/CepController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Services\CepService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class CepController extends Controller
{
    public function __construct(private readonly CepService $cepService) {}

    public function index(): View
    {
        return view('cep');
    }

    public function consultar(Request $request): View
    {
        $request->validate([
            'cep' => ['required', 'string'],
        ], [
            'cep.required' => 'Informe um CEP para consultar.',
        ]);

        try {
            $endereco = $this->cepService->consultar($request->input('cep'));

            if (! $endereco) {
                return view('cep', [
                    'cepInformado' => $request->input('cep'),
                    'erro' => 'CEP não encontrado.',
                ]);
            }

            return view('cep', [
                'cepInformado' => $request->input('cep'),
                'endereco' => $endereco,
            ]);
        } catch (RuntimeException $e) {
            return view('cep', [
                'cepInformado' => $request->input('cep'),
                'erro' => $e->getMessage(),
            ]);
        }
    }
}
```

### Passo 9.3 — Registrar as rotas da tela em `routes/web.php`

```php
use App\Http\Controllers\CepController;

Route::get('/cep', [CepController::class, 'index'])->name('cep.index');
Route::post('/cep', [CepController::class, 'consultar'])->name('cep.consultar');
```

### Passo 9.4 — Criar a tela de Consulta de CEP (`resources/views/cep.blade.php`)

Crie o arquivo `resources/views/cep.blade.php`:

```html
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de CEP</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #f8fafc;
            padding: 20px;
        }
        .card {
            width: 100%;
            max-width: 460px;
            background: #ffffff;
            color: #1e293b;
            border-radius: 14px;
            padding: 32px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.35);
        }
        h1 { margin: 0 0 8px; font-size: 1.5rem; color: #0f172a; }
        p.subtitle { margin: 0 0 24px; font-size: 0.92rem; color: #64748b; }
        label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem; }
        .input-group { display: flex; gap: 10px; }
        input[type="text"] {
            flex: 1;
            padding: 12px 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.2s;
        }
        input[type="text"]:focus { border-color: #2563eb; }
        button {
            padding: 12px 20px;
            background: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover { background: #1d4ed8; }
        .alert-error {
            margin-top: 20px;
            padding: 12px 14px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            border-radius: 8px;
            font-size: 0.92rem;
        }
        .result {
            margin-top: 24px;
            padding: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }
        .result h2 { margin: 0 0 12px; font-size: 1.05rem; color: #0f172a; }
        .result-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #e2e8f0; font-size: 0.92rem; }
        .result-row:last-child { border-bottom: none; }
        .result-label { color: #64748b; font-weight: 600; }
    </style>
</head>
<body>
    <main class="card">
        <h1>Consulta de CEP</h1>
        <p class="subtitle">Digite um CEP brasileiro para consultar o endereço.</p>

        <form method="POST" action="{{ route('cep.consultar') }}">
            @csrf
            <label for="cep">CEP</label>
            <div class="input-group">
                <input
                    type="text"
                    id="cep"
                    name="cep"
                    placeholder="Ex: 50030-230"
                    value="{{ old('cep', $cepInformado ?? '') }}"
                    maxlength="9"
                >
                <button type="submit">Consultar</button>
            </div>
        </form>

        @error('cep')
            <div class="alert-error">{{ $message }}</div>
        @enderror

        @if (! empty($erro))
            <div class="alert-error">{{ $erro }}</div>
        @endif

        @if (! empty($endereco))
            <section class="result" aria-label="Resultado da consulta">
                <h2>Endereço Encontrado</h2>
                <div class="result-row">
                    <span class="result-label">CEP</span>
                    <span>{{ $endereco['cep'] }}</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Logradouro</span>
                    <span>{{ $endereco['logradouro'] }}</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Bairro</span>
                    <span>{{ $endereco['bairro'] }}</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Cidade / UF</span>
                    <span>{{ $endereco['localidade'] }} - {{ $endereco['uf'] }}</span>
                </div>
            </section>
        @endif
    </main>
</body>
</html>
```

> Você pode testar a tela manualmente acessando `http://127.0.0.1:8000/cep` com `php artisan serve` rodando.

### Passo 9.5 — Criar o Teste Automatizado no Navegador com X Requisições

Crie o arquivo `tests/Browser/CepBrowserTest.php`:

```php
<?php

use Illuminate\Support\Facades\Http;

it('deve realizar múltiplas requisições de consulta de CEP no navegador', function () {
    // Quantidade X de requisições que o navegador irá executar
    $quantidadeRequisicoes = 5;

    $cepsParaTestar = [
        ['cep' => '50030-230', 'limpo' => '50030230', 'logradouro' => 'Cais do Apolo', 'bairro' => 'Recife', 'localidade' => 'Recife', 'uf' => 'PE'],
        ['cep' => '01001-000', 'limpo' => '01001000', 'logradouro' => 'Praça da Sé', 'bairro' => 'Sé', 'localidade' => 'São Paulo', 'uf' => 'SP'],
        ['cep' => '20040-020', 'limpo' => '20040020', 'logradouro' => 'Rua da Assembleia', 'bairro' => 'Centro', 'localidade' => 'Rio de Janeiro', 'uf' => 'RJ'],
        ['cep' => '70040-010', 'limpo' => '70040010', 'logradouro' => 'SBN Quadra 1', 'bairro' => 'Asa Norte', 'localidade' => 'Brasília', 'uf' => 'DF'],
        ['cep' => '40020-000', 'limpo' => '40020000', 'logradouro' => 'Praça Castro Alves', 'bairro' => 'Centro', 'localidade' => 'Salvador', 'uf' => 'BA'],
    ];

    // Simulamos o ViaCEP para que o teste de navegador seja rápido e determinístico
    $fakes = [];
    foreach ($cepsParaTestar as $item) {
        $fakes["viacep.com.br/ws/{$item['limpo']}/json/"] = Http::response([
            'cep' => $item['cep'],
            'logradouro' => $item['logradouro'],
            'bairro' => $item['bairro'],
            'localidade' => $item['localidade'],
            'uf' => $item['uf'],
        ], 200);
    }
    Http::fake($fakes);

    $page = visit('/cep');
    $page->assertSee('Consulta de CEP');

    for ($i = 0; $i < $quantidadeRequisicoes; $i++) {
        $item = $cepsParaTestar[$i % count($cepsParaTestar)];

        $page->fill('cep', $item['cep'])
            ->click('Consultar')
            ->assertSee('Endereço Encontrado')
            ->assertSee($item['logradouro'])
            ->assertSee($item['localidade']);
    }
});

it('deve exibir mensagem de erro no navegador ao consultar CEP inexistente', function () {
    Http::fake([
        'viacep.com.br/ws/99999999/json/' => Http::response(['erro' => true], 200),
    ]);

    visit('/cep')
        ->fill('cep', '99999-999')
        ->click('Consultar')
        ->assertSee('CEP não encontrado.');
});
```

### Passo 9.6 — Executar o Teste de Navegador

```bash
# Modo headless (sem abrir janela visual — mais rápido)
./vendor/bin/pest tests/Browser/CepBrowserTest.php

# Modo visual (abre o navegador e executa a automação acontecendo em tempo real!)
./vendor/bin/pest tests/Browser/CepBrowserTest.php --headed
```

> **Dica:** Altere a variável `$quantidadeRequisicoes` (por exemplo, para `10` ou `20`) e execute com `--headed` para demonstrar ao vivo o navegador preenchendo o campo e clicando em **Consultar** automaticamente!

---

## ✅ Executando Toda a Suíte ao Final da Aula

```bash
./vendor/bin/pest
```

Todos os testes (**Unit**, **Feature** e **Browser**) serão executados em sequência, validando toda a aplicação construída durante a prática!
