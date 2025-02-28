import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { TokenStorageService } from './token-storage.service';

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  apiUrl:string = environment.apiUrl.substring(0, environment.apiUrl.length - 1);
  httpOptions = {
    headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', Accept: '*/*' })
  };

  constructor(private http: HttpClient, private token: TokenStorageService) { }

  post<T>(url: string, data: Record<string, string> = {}, httpOptions: Record<string, any> = {}): Observable<T> {
    const body = new URLSearchParams();
    for (const key in data) {
      body.set(key, data[key]);
    }

    if (Object.keys(httpOptions).length == 0) {
      httpOptions = this.httpOptions;
    }

    if (this.token.getUser() !== null && this.token.getToken()) {
      httpOptions.headers = httpOptions.headers.set('Authorization', 'Bearer ' + this.token.getToken());
    }

    return this.http.post<T>(this.apiUrl + url, body.toString(), httpOptions);
  }

  get<T>(url: string, httpOptions: Record<string, any> = {}): Observable<T> {
    if (Object.keys(httpOptions).length == 0) {
      httpOptions = this.httpOptions;
    }

    if (this.token.getUser() !== null && this.token.getToken()) {
      httpOptions.headers = httpOptions.headers.set('Authorization', 'Bearer ' + this.token.getToken());
    }

    return this.http.get<T>(this.apiUrl + url, httpOptions);
  }

  delete<T>(url: string, httpOptions: Record<string, any> = {}): Observable<T> {
    if (Object.keys(httpOptions).length == 0) {
      httpOptions = this.httpOptions;
    }

    if (this.token.getUser() !== null && this.token.getToken()) {
      httpOptions.headers = httpOptions.headers.set('Authorization', 'Bearer ' + this.token.getToken());
    }

    return this.http.delete<T>(this.apiUrl + url, httpOptions);
  }
}
