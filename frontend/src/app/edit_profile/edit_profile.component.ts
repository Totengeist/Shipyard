import { NgIf } from '@angular/common';
import { Component, OnInit } from '@angular/core'; // eslint-disable-line import/named
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { TokenStorageService } from '../_services/token-storage.service';
import { UserService } from '../_services/user.service';
import { UserInterface } from '../_types/user.interface';

@Component({
  selector: 'app-edit-profile',
  templateUrl: './edit_profile.component.html',
  standalone: true,
  imports: [NgIf, FormsModule]
})
export class EditProfileComponent implements OnInit {
  currentUser: UserInterface = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    ref: '',
  };
  steamError = '';
  discordError = '';

  constructor(private userService: UserService, private route: ActivatedRoute, private token: TokenStorageService) {  }

  ngOnInit(): void {
    const user = this.token.getUser();
    if (user) {
      this.currentUser = user;
    }
  }

  onSubmit(): void {
    const login = document.getElementById('submit') as HTMLButtonElement;
    if ( login !== null ) {
      login.disabled = true;
    }
    const user = {
      username: this.currentUser.name,
      email: this.currentUser.email,
      password: this.currentUser.password,
      password_confirmation: this.currentUser.password_confirmation,
      ref: this.currentUser.ref
    };
    this.userService.edit(user);
  }
}
