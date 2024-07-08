import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { TokenStorageService } from '../../_services/token-storage.service';
import { environment } from '../../../environments/environment';


@Component({
  selector: 'app-edit-permissions',
  templateUrl: './edit-permissions.component.html',
  styleUrls: ['./edit-permissions.component.css']
})
export class AdminEditPermissionsComponent implements OnInit {
  slug = '';
  label = '';

  constructor(private token: TokenStorageService, private http: HttpClient, private activatedRoute: ActivatedRoute) { }

  ngOnInit(): void {
    this.slug = this.activatedRoute.snapshot.params.slug;
    this.getPermission().subscribe(
      data => {
        this.slug = data.slug;
        this.label = data.label;
      },
      err => {
        console.log('Error');
      }
    );
  }

  getPermission(): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({
        'Content-Type': 'application/x-www-form-urlencoded',
        Accept: '*/*',
        Authorization: 'Bearer ' + this.token.getToken()
      })
    };

    return this.http.get(environment.apiUrl + 'permission/' + this.slug, httpOptions);
  }

}
