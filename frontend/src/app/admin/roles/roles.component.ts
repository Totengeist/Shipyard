import { NgFor } from '@angular/common';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Component, OnInit } from '@angular/core'; // eslint-disable-line import/named
import { RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { TokenStorageService } from '../../_services/token-storage.service';


@Component({
  selector: 'app-roles',
  templateUrl: './roles.component.html',
  styleUrls: ['./roles.component.css'],
  standalone: true,
  imports: [NgFor, RouterLink]
})
export class AdminRolesComponent implements OnInit {
  roles: any[] = [];

  constructor(private token: TokenStorageService, private http: HttpClient) { }

  ngOnInit(): void {
    this.getRoles().subscribe(
      data => {
        if ( data !== null ) {
          data.forEach((element: any) => {
            this.roles.push({label: element.label, slug: element.slug});
          });
        }
      },
      () => {
        console.log('Error');
      }
    );
  }

  getRoles(): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({
        'Content-Type': 'application/x-www-form-urlencoded',
        Accept: '*/*',
        Authorization: 'Bearer ' + this.token.getToken()
      })
    };

    return this.http.get(environment.apiUrl + 'role', httpOptions);
  }


}
