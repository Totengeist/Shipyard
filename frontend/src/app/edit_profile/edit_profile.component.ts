import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { TokenStorageService } from '../_services/token-storage.service';
import { UserService } from '../_services/user.service';
import { NgIf } from '@angular/common';

@Component({
  selector: 'app-edit-profile',
  templateUrl: './edit_profile.component.html',
  styleUrls: ['./edit_profile.component.css'],
  standalone: true,
  imports: [NgIf, FormsModule]
})
export class EditProfileComponent implements OnInit {
  currentUser: User = {
    name: null,
    email: null,
    password: null,
    password_confirmation: null,
    ref: '',
  };
  steamError = '';
  discordError = '';

  constructor(private userService: UserService, private route: ActivatedRoute, private token: TokenStorageService) {  }

  ngOnInit(): void {
    this.currentUser = this.token.getUser();
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

interface User {
    name: string|null,
    email: string|null,
    password: string|null,
    password_confirmation: string|null,
    ref: string,
}
