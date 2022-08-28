import { Component, OnInit } from '@angular/core';
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { TokenStorageService } from '../../_services/token-storage.service';

const AUTH_API = 'http://localhost/Shipyard/api/v1/';


@Component({
  selector: 'app-roles',
  templateUrl: './roles.component.html',
  styleUrls: ['./roles.component.css']
})
export class AdminRolesComponent implements OnInit {
  roles: any[] = [];

  constructor(private token: TokenStorageService, private http: HttpClient) { }

  ngOnInit(): void {
      this.getRoles().subscribe(
      data => {
        data.forEach((element: any) => {
            this.roles.push({label: element.label, slug: element.slug});
        });
      },
      err => {
        console.log("Error");
      }
    );
  }
  
  getRoles(): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': '*/*', 'Authorization': 'Bearer ' + this.token.getToken() })
    };

    return this.http.get(AUTH_API + 'role', httpOptions);
  }


}
