export interface ApiResponse<T> {
    data:       Data<T>;
    status:     number;
    statusText: string;
    headers:    Headers;
    config:     Config;
    request:    Request;
    response?:  Response<T>;
}

export interface Response<T> {
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
    // data:    T | Array<any> | null;
    data:    T | null;
    status:  string;
    message: string;
    code:    number;
    meta:    Array<any>;
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
