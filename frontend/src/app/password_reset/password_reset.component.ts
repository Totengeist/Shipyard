import { NgFor, NgIf } from '@angular/common';
import { Component, OnInit } from '@angular/core'; // eslint-disable-line import/named
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';

import { ApiService } from '../_services/api.service';

@Component({
  selector: 'app-profile',
  templateUrl: './password_reset.component.html',
  standalone: true,
  imports: [NgFor, NgIf, RouterLink, FormsModule]
})
export class PasswordResetComponent implements OnInit {
  token = '';
  request = false;
  send = false;
  error = false;
  submit: HTMLButtonElement|null = null;
  email: HTMLInputElement|null = null;
  password: HTMLInputElement|null = null;
  passwordConfirmation: HTMLInputElement|null = null;

  constructor(private api: ApiService, private route: ActivatedRoute) { }

  ngOnInit(): void {
    this.submit = document.getElementById('submit') as HTMLButtonElement;
    this.route.params.subscribe(params => {
      if (typeof params.token !== 'undefined') {
        this.token = params.token;
      }
    });
  }

  requestReset(): void {
    this.error = false;
    this.email = document.getElementById('email') as HTMLInputElement;
    const body: Record<string, string> = {};
    body.email = this.email!.value;

    this.api.post('/password_reset', body).subscribe(
      () => {
        this.request = true;
      },
      () => {
        if ( this.submit !== null ) {
          this.submit.disabled = false;
        }
        this.error = true;
      }
    );
  }

  sendReset(): void {
    this.error = false;
    this.password = document.getElementById('password') as HTMLInputElement;
    this.passwordConfirmation = document.getElementById('password_confirmation') as HTMLInputElement;
    const body: Record<string, string> = {};
    body.password = this.password!.value;
    body.password_confirmation = this.passwordConfirmation!.value;

    this.api.post(`/password_reset/${this.token}`, body).subscribe(
      () => {
        this.send = true;
      },
      () => {
        if ( this.submit !== null ) {
          this.submit.disabled = false;
        }
        this.error = true;
      }
    );
  }

  onSubmit(): void {
    if ( this.submit !== null ) {
      this.submit.disabled = true;
    }

    if (this.token == '') {
      this.requestReset();
    } else {
      this.sendReset();
    }
  }
}
