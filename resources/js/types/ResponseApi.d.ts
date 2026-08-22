export interface ApiResponse<D, E = {}> {
    data: Data<D, E>;
    status: number;
    statusText: string;
    headers: Headers;
    config: Config;
    request: Request;
    response?: Response<D, E>;
}

export interface Response<D, E> {
    data: Data<D, E>;
    status: number;
    statusText: string;
    headers: Headers;
    config: Config;
    request: Request;
}

export interface Config {
    transitional: Transitional;
    adapter: string[];
    transformRequest: null[];
    transformResponse: null[];
    timeout: number;
    xsrfCookieName: string;
    xsrfHeaderName: string;
    maxContentLength: number;
    maxBodyLength: number;
    env: Request;
    headers: ConfigHeaders;
    method: string;
    url: string;
}

export interface Request {}

export interface ConfigHeaders {
    accept: string;
    xXsrfToken: string;
}

export interface Transitional {
    silentJSONParsing: boolean;
    forcedJSONParsing: boolean;
    clarifyTimeoutError: boolean;
}

export interface Data<D, E = {}> {
    // data:    T | Array<any> | null;
    data: D | null;
    status: string;
    message: string;
    code: number;
    meta: Array<any>;
    errors?: E;
    pagination?: {
        total: number;
        current_page: number;
        per_page: number;
        last_page: number;
    };
}

export interface Headers {
    cacheControl: string;
    connection: string;
    contentType: string;
    date: string;
    server: string;
    transferEncoding: string;
    xPoweredBy: string;
}

/*
|--------------------------------------------------------------------------
| Sobres de paginación (hallazgo N29)
|--------------------------------------------------------------------------
|
| La API no tiene uno, tiene TRES, y cada página del backoffice está escrita contra el
| suyo. La deuda de tipos de N29 tapaba esto: mientras los servicios declaraban
| `ApiResponse<T>` devolviendo otra cosa, TypeScript no podía notar que `order/filter` y
| `product/filter` no se parecen en nada.
|
| Verificado consultando los endpoints reales con una sesión autenticada, no leyendo el
| código: son las tres formas que salen de verdad por el cable.
|
|   1. `Data<T[]>`                   data = [], y `pagination` colgando de la raíz.
|                                    ApiResponse::Pagination() del backend.
|                                    product, brand, category, attribute, coupon,
|                                    shipping/zones, tax/rates
|
|   2. `Data<PaginatedPayload<T>>`   data = { data: [], pagination: {...} }
|                                    billing, customer, order
|
|   3. `Data<FlatPaginatedPayload<T>>`  data = { data: [], total, per_page, ... }
|                                    review, shipment
|
| No se unifican aquí a propósito: unificar es cambiar respuestas HTTP en producción, y
| es una decisión aparte. Lo que hacen estos tipos es dejar de mentir sobre lo que hay.
*/

export interface PaginationMeta {
    total: number;
    current_page: number;
    per_page: number;
    last_page: number;
}

/** Sobre 2: la paginación viaja como objeto dentro de `data`. */
export interface PaginatedPayload<T> {
    data: T[];
    pagination: PaginationMeta;
}

/** Sobre 3: los campos de paginación van sueltos junto a `data`. */
export interface FlatPaginatedPayload<T> extends PaginationMeta {
    data: T[];
}
