import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { UserInterface } from '../_types/user.interface';
import { ApiService } from './api.service';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  constructor(private api: ApiService) { }

  login(email: string, password: string): Observable<UserInterface> {
    const body = {
      email: email,
      password: password
    };

    return this.api.post<UserInterface>('/login', body);
  }

  logout(): Observable<any> {
    return this.api.post('/logout');
  }

  me(): Observable<UserInterface> {
    return this.api.get<UserInterface>('/me');
  }

  register(name: string, email: string, password: string, passwordConfirmation: string): Observable<any> {
    const body = {
      name: name,
      email: email,
      password: password,
      password_confirmation: passwordConfirmation
    };

    return this.api.post('/register', body);
  }

  edit(ref: string, name: string|null, email: string|null, password: string|undefined, passwordConfirmation: string|undefined): Observable<any> {
    const body: Record<string, string> = {};
    if( name !== null ) {
      body.name = name;
    }
    if( email !== null ) {
      body.email = email;
    }
    if( password !== undefined && password === passwordConfirmation ) {
      body.password = password;
      body.password_confirmation = passwordConfirmation;
    }

    return this.api.post(`/user/${ref}`, body);
  }
}
