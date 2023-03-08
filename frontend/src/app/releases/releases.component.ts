import { Component, OnInit } from '@angular/core';
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { TokenStorageService } from '../_services/token-storage.service';
import { environment } from '../../environments/environment';

@Component({
  selector: 'app-releases',
  templateUrl: './releases.component.html',
  styleUrls: ['./releases.component.css']
})
export class ReleasesComponent implements OnInit {
  releases: any[] = [];

  constructor(private token: TokenStorageService, private http: HttpClient) { }

  ngOnInit(): void {
      this.getReleases().subscribe(
      data => {
        data.forEach((element: any) => {
            this.releases.push({label: element.label, slug: element.slug});
        });
      },
      err => {
        console.log("Error");
      }
    );
  }
  
  getReleases(): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': '*/*' })
    };

    return this.http.get(environment.apiUrl + 'release', httpOptions);
  }

}
