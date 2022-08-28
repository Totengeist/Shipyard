import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

const httpOptions = {
  headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': '*/*' })
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

    console.log(httpOptions.headers.get('Content-Type'));

    return this.http.post(environment.apiUrl + 'login', body.toString(), httpOptions);
  }

  register(name: string, email: string, password: string, password_confirmation: string): Observable<any> {
    return this.http.post(environment.apiUrl + 'register', {
      name,
      email,
      password,
      password_confirmation
    }, httpOptions);
  }
}