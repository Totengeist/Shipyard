import { NgFor } from '@angular/common';
import { Component, OnInit } from '@angular/core'; // eslint-disable-line import/named
import { RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { ApiService } from '../../_services/api.service';
import { TokenStorageService } from '../../_services/token-storage.service';


@Component({
  selector: 'app-roles',
  templateUrl: './roles.component.html',
  standalone: true,
  imports: [NgFor, RouterLink]
})
export class AdminRolesComponent implements OnInit {
  roles: any[] = [];

  constructor(private api: ApiService, private token: TokenStorageService) { }

  ngOnInit(): void {
    this.getRoles().subscribe(
      data => {
        if ( data.data !== null ) {
          data.data.forEach((element: any) => {
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
    return this.api.get('/role');
  }


}
