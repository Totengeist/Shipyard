import { Component } from '@angular/core';
import { AuthService } from '../_services/auth.service';
import { FormsModule } from '@angular/forms';
import { NgIf } from '@angular/common';

@Component({
  selector: 'app-register',
  templateUrl: './register.component.html',
  styleUrls: ['./register.component.css'],
  standalone: true,
  imports: [NgIf, FormsModule]
})
export class RegisterComponent {
  form: RegisterFormData = {
    name: null,
    email: null,
    password: null,
    password_confirmation: null
  };
  isSuccessful = false;
  isSignUpFailed = false;
  errorMessage = '';

  constructor(private authService: AuthService) { }

  onSubmit(): void {
    const { name, email, password, password_confirmation } = this.form;

    if( name !== null && email !== null && password !== null && password_confirmation !== null ) {
      this.authService.register(name, email, password, password_confirmation).subscribe(
        data => {
          console.log(data);
          this.isSuccessful = true;
          this.isSignUpFailed = false;
        },
        err => {
          this.errorMessage = err.error.message;
          this.isSignUpFailed = true;
        }
      );
    }
  }
}

interface RegisterFormData {
    name: string|null,
    email: string|null,
    password: string|null,
    password_confirmation: string|null
}
