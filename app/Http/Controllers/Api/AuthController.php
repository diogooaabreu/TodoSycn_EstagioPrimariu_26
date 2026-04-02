<?php

/**
 * ============================================================
 * CONTROLLER: AuthController
 * ============================================================
 * Gere toda a autenticação via API REST.
 *
 * Diferença do Livewire:
 * - Livewire usa sessões + cookies (para o browser)
 * - Esta API usa tokens (para o Android/iOS)
 *
 * Fluxo do token:
 * 1. Android envia email + password para POST /api/auth/login
 * 2. Laravel verifica as credenciais
 * 3. Laravel gera um token único e devolve-o em JSON
 * 4. Android guarda o token localmente (SecureStore)
 * 5. Em cada pedido seguinte, Android envia: Authorization: Bearer TOKEN
 * 6. Laravel verifica o token e identifica o utilizador
 *
 * Endpoints:
 * POST /api/auth/register → criar conta
 * POST /api/auth/login    → fazer login, recebe token
 * POST /api/auth/logout   → invalidar token
 * GET  /api/auth/me       → dados do utilizador autenticado
 * ============================================================
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registo de novo utilizador.
     *
     * Recebe: name, email, password
     * Devolve: token de acesso + dados básicos do utilizador
     *
     * POST /api/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        // Valida os dados recebidos antes de fazer qualquer coisa
        // Se a validação falhar, o Laravel devolve automaticamente um 422
        // com os erros em JSON — não precisamos de tratar manualmente
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],  // email único na BD
            'password' => ['required', Password::min(8)],               // mínimo 8 caracteres
        ]);

        // Cria o utilizador na base de dados
        // Hash::make() encripta a password — nunca guardar em texto simples
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Gera um token de acesso para este utilizador
        // 'mobile' é o nome do token — útil para identificar de onde veio
        $token = $user->createToken('mobile')->plainTextToken;

        // Devolve 201 Created com o token e os dados do utilizador
        // NUNCA incluímos a password na resposta
        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ], 201); // 201 = Created (diferente de 200 = OK)
    }

    /**
     * Login de utilizador existente.
     *
     * PROBLEMA DO CÓDIGO ORIGINAL:
     * - Devolvia o objecto $user directamente → expunha a password em JSON
     * - Não gerava token → o Android não tinha como autenticar pedidos seguintes
     * - Não validava os campos → erros PHP feios em vez de mensagens claras
     *
     * Recebe: email, password
     * Devolve: token de acesso + dados básicos do utilizador
     *
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        // Validação básica dos campos obrigatórios
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Procura o utilizador pelo email
        $user = User::where('email', $request->email)->first();

        // Verifica se o utilizador existe E se a password está correcta
        // Hash::check() compara a password em texto com o hash guardado na BD
        // Usamos a mesma condição para ambos os casos por segurança:
        // não revelamos se o email existe ou não (prevenção de user enumeration)
        if (!$user || !Hash::check($request->password, $user->password)) {
            // ValidationException para devolver 422 com o erro no campo correcto
            throw ValidationException::withMessages([
                'email' => ['As credenciais introduzidas estão incorrectas.'],
            ]);
        }

        // Apaga tokens antigos deste dispositivo antes de criar um novo
        // Evita acumulação de tokens inválidos na base de dados
        $user->tokens()->where('name', 'mobile')->delete();

        // Gera novo token de acesso
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Logout — invalida o token actual.
     *
     * O Android deve apagar o token guardado localmente após este pedido.
     * Ao contrário do logout web (que destroi a sessão),
     * aqui apenas apagamos o token da base de dados.
     *
     * POST /api/auth/logout
     * Requer: Authorization: Bearer TOKEN no cabeçalho
     */
    public function logout(Request $request): JsonResponse
    {
        // currentAccessToken() devolve o token que foi usado neste pedido
        // delete() apaga-o da tabela personal_access_tokens
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sessão terminada com sucesso.',
        ]);
    }

    /**
     * Devolve os dados do utilizador autenticado.
     * Útil para o Android verificar se o token ainda é válido
     * e obter informações actualizadas do utilizador.
     *
     * GET /api/auth/me
     * Requer: Authorization: Bearer TOKEN no cabeçalho
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user(); // o utilizador identificado pelo token

        return response()->json([
            'id'              => $user->id,
            'name'            => $user->name,
            'email'           => $user->email,
            // Estatísticas úteis para o ecrã de perfil
            'total_tarefas'   => $user->todos()->count(),
            'criado_em'       => $user->created_at->toDateString(),
        ]);
    }
}
