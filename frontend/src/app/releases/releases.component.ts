import { Component, OnInit } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { TokenStorageService } from '../_services/token-storage.service';
import { environment } from '../../environments/environment';
import { RouterLink } from '@angular/router';
import { NgFor } from '@angular/common';

@Component({
  selector: 'app-releases',
  templateUrl: './releases.component.html',
  styleUrls: ['./releases.component.css'],
  standalone: true,
  imports: [NgFor, RouterLink]
})
export class ReleasesComponent implements OnInit {
  releases: Release[] = [];

  constructor(private token: TokenStorageService, private http: HttpClient) { }

  ngOnInit(): void {
    this.getReleases().subscribe(
      data => {
        data.data.forEach((element: Release) => {
          this.releases.push({label: element.label, slug: element.slug});
        });
      },
      () => {
        console.log('Error');
      }
    );
  }

  getReleases(): Observable<any> { // eslint-disable-line @typescript-eslint/no-explicit-any
    const httpOptions = {
      headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', Accept: '*/*' })
    };

    return this.http.get(environment.apiUrl + 'release', httpOptions);
  }

}

interface Release { label: string, slug: string }
