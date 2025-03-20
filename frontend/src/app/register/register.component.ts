import { NgIf } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { environment } from '../../environments/environment';
import { AuthService } from '../_services/auth.service';

@Component({
  selector: 'app-register',
  templateUrl: './register.component.html',
  standalone: true,
  imports: [NgIf, FormsModule]
})
export class RegisterComponent {
  form: Record<string,string> = {};
  isSuccessful = false;
  isSignUpFailed = false;
  errorMessage = '';
  url: string = environment.standardUrl;

  constructor(private authService: AuthService) { }

  onSubmit(): void {
    const { name, email, password, password_confirmation } = this.form;

    if( name !== null && email !== null && password !== null && password_confirmation !== null ) {
      this.authService.register(name, email, password, password_confirmation).subscribe(
        () => {
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
