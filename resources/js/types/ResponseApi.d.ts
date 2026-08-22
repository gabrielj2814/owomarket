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
    /** Sólo en respuestas paginadas. Ver la nota sobre paginación al final del fichero. */
    pagination?: PaginationMeta;
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
| Paginación (hallazgos N29 y N37)
|--------------------------------------------------------------------------
|
| **Hay un solo formato**, y lo emite `ApiResponse::paginated()` en el backend:
|
|     { status, code, message, data: T[], pagination: PaginationMeta, meta }
|
| Es decir: `data` es siempre el payload —igual que en las respuestas sin paginar— y los
| contadores viajan aparte, en `pagination`, que `Data<T>` ya declara como opcional.
|
| Hasta N37 convivían SEIS formas distintas en el cable, y cada página del backoffice
| estaba escrita contra la suya. La deuda de tipos de N29 lo tapaba: mientras los
| servicios declaraban una cosa y devolvían otra, TypeScript no podía notar que dos
| endpoints hermanos no se parecían en nada.
|
| Si aparece un endpoint paginado que no encaja aquí, el sitio que hay que arreglar es el
| controlador, no este fichero.
*/

export interface PaginationMeta {
    total: number;
    current_page: number;
    per_page: number;
    last_page: number;
}
