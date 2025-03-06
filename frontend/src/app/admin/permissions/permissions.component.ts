import { NgFor } from '@angular/common';
import { Component, OnInit } from '@angular/core'; // eslint-disable-line import/named
import { RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { ApiService } from '../../_services/api.service';
import { TokenStorageService } from '../../_services/token-storage.service';

@Component({
  selector: 'app-permissions',
  templateUrl: './permissions.component.html',
  standalone: true,
  imports: [NgFor, RouterLink]
})
export class AdminPermissionsComponent implements OnInit {
  permissions: any[] = [];

  constructor(private api: ApiService, private token: TokenStorageService) { }

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
    return this.api.get('/permission');
  }


}
