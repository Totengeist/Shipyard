import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Component, OnInit } from '@angular/core'; // eslint-disable-line import/named
import { ActivatedRoute } from '@angular/router';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { TokenStorageService } from '../../_services/token-storage.service';


@Component({
  selector: 'app-edit-permissions',
  templateUrl: './edit-permissions.component.html',
  styleUrls: ['./edit-permissions.component.css'],
  standalone: true
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
      () => {
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
