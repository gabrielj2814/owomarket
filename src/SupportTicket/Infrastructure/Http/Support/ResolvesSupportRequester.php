<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Support;

use Illuminate\Http\Request;

/**
 * Resuelve quién hace la petición a la mesa de soporte pública, SIEMPRE desde
 * la sesión — nunca desde 'user_id', 'sender_type' o 'requester_type' del
 * cuerpo o la query string del request.
 *
 * Antes (hallazgo A6) cualquiera podía pasar {"user_id":"<uuid ajeno>"} para
 * leer o escribir en el ticket de otra persona, e incluso
 * {"sender_type":"admin"} para insertar mensajes que la víctima veía como
 * oficiales de OwoMarket (phishing dentro del propio producto).
 */
trait ResolvesSupportRequester
{
    /**
     * @return array{id: string, type: 'tenant_owner'|'customer', name: string}|null
     */
    private function resolveSupportRequester(Request $request): ?array
    {
        $user = $request->user();

        if ($user !== null) {
            return [
                'id' => (string) $user->id,
                'type' => 'tenant_owner',
                'name' => (string) ($user->name ?? 'Propietario de tienda'),
            ];
        }

        $customerId = $request->session()->get('central_customer_id');

        if ($customerId) {
            return [
                'id' => (string) $customerId,
                'type' => 'customer',
                'name' => (string) ($request->session()->get('customer_name') ?: 'Cliente'),
            ];
        }

        return null;
    }
}
