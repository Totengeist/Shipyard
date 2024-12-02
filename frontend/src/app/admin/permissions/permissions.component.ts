import { NgFor } from '@angular/common';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Component, OnInit } from '@angular/core'; // eslint-disable-line import/named
import { RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { TokenStorageService } from '../../_services/token-storage.service';

@Component({
  selector: 'app-permissions',
  templateUrl: './permissions.component.html',
  styleUrls: ['./permissions.component.css'],
  standalone: true,
  imports: [NgFor, RouterLink]
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
      () => {
        console.log('Error');
      }
    );
  }

  getPermissions(): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', Accept: '*/*' })
    };

    return this.http.get(environment.apiUrl + 'permission', httpOptions);
  }


}
