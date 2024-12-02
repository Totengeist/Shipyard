import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

const httpOptions = {
  headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', Accept: '*/*' })
};

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  constructor(private http: HttpClient) { }

  login(email: string, password: string): Observable<any> {
    const body = new URLSearchParams();
    body.set('email', email);
    body.set('password', password);

    return this.http.post(environment.apiUrl + 'login', body.toString(), httpOptions);
  }

  logout(): Observable<any> {
    return this.http.post(environment.apiUrl + 'logout', (new URLSearchParams()).toString(), httpOptions);
  }

  me(): Observable<any> {
    return this.http.get(environment.apiUrl + 'me', httpOptions);
  }

  register(name: string, email: string, password: string, passwordConfirmation: string): Observable<any> {
    const body = new URLSearchParams();
    body.set('name', name);
    body.set('email', email);
    body.set('password', password);
    body.set('password_confirmation', passwordConfirmation);

    return this.http.post(environment.apiUrl + 'register', body.toString(), httpOptions);
  }

  edit(ref: string, name: string|null, email: string|null, password: string|null, passwordConfirmation: string|null): Observable<any> {
    const body = new URLSearchParams();
    if( name !== null ) {
      body.set('name', name);
    }
    if( email !== null ) {
      body.set('email', email);
    }
    if( password !== null && password === passwordConfirmation ) {
      body.set('password', password);
      body.set('password_confirmation', passwordConfirmation);
    }

    return this.http.post(environment.apiUrl + 'user/' + ref, body.toString(), httpOptions);
  }
}
