export interface ApiResponse<T> {
    data:       Data<T>;
    status:     number;
    statusText: string;
    headers:    Headers;
    config:     Config;
    request:    Request;
}

export interface Config {
    transitional:      Transitional;
    adapter:           string[];
    transformRequest:  null[];
    transformResponse: null[];
    timeout:           number;
    xsrfCookieName:    string;
    xsrfHeaderName:    string;
    maxContentLength:  number;
    maxBodyLength:     number;
    env:               Request;
    headers:           ConfigHeaders;
    method:            string;
    url:               string;
}

export interface Request {
}

export interface ConfigHeaders {
    accept:     string;
    xXsrfToken: string;
}

export interface Transitional {
    silentJSONParsing:   boolean;
    forcedJSONParsing:   boolean;
    clarifyTimeoutError: boolean;
}

export interface Data<T> {
    data:    T | null;
    mensaje: string;
    code:    number;
}

export interface Headers {
    cacheControl:     string;
    connection:       string;
    contentType:      string;
    date:             string;
    server:           string;
    transferEncoding: string;
    xPoweredBy:       string;
}
