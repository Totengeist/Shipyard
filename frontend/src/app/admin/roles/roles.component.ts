import { NgFor } from '@angular/common';
import { Component, OnInit } from '@angular/core'; // eslint-disable-line import/named
import { RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { ApiService } from '../../_services/api.service';
import { TokenStorageService } from '../../_services/token-storage.service';
import { PaginationInterface } from '../../_types/pagination.interface';
import { RoleInterface } from '../../_types/role.interface';


@Component({
  selector: 'app-roles',
  templateUrl: './roles.component.html',
  standalone: true,
  imports: [NgFor, RouterLink]
})
export class AdminRolesComponent implements OnInit {
  roles: RoleInterface[] = [];

  constructor(private api: ApiService, private token: TokenStorageService) { }

  ngOnInit(): void {
    this.getRoles().subscribe(
      data => {
        if ( data.data !== null ) {
          data.data.forEach((element: RoleInterface) => {
            this.roles.push({label: element.label, slug: element.slug});
          });
        }
      },
      () => {
        console.log('Error');
      }
    );
  }

  getRoles(): Observable<PaginationInterface<RoleInterface>> {
    return this.api.get('/role');
  }


}
