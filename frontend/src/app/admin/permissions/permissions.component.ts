import { Component, OnInit } from '@angular/core';
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { TokenStorageService } from '../../_services/token-storage.service';
import { environment } from '../../../environments/environment';


@Component({
  selector: 'app-permissions',
  templateUrl: './permissions.component.html',
  styleUrls: ['./permissions.component.css']
})
export class AdminPermissionsComponent implements OnInit {
  permissions: any[] = [];

  constructor(private token: TokenStorageService, private http: HttpClient) { }

  ngOnInit(): void {
      this.getPermissions().subscribe(
      data => {
        data.forEach((element: any) => {
            this.permissions.push({label: element.label, slug: element.slug});
        });
      },
      err => {
        console.log("Error");
      }
    );
  }
  
  getPermissions(): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': '*/*', 'Authorization': 'Bearer ' + this.token.getToken() })
    };

    return this.http.get(environment.apiUrl + 'permission', httpOptions);
  }


}