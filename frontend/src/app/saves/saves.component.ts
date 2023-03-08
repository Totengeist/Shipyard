import { Component, OnInit } from '@angular/core';
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { TokenStorageService } from '../_services/token-storage.service';
import { environment } from '../../environments/environment';

@Component({
  selector: 'app-saves',
  templateUrl: './saves.component.html',
  styleUrls: ['./saves.component.css']
})
export class SavesComponent implements OnInit {
  saves: any[] = [];

  constructor(private token: TokenStorageService, private http: HttpClient) { }

  ngOnInit(): void {
      this.getSaves().subscribe(
      data => {
        data.forEach((element: any) => {
            this.saves.push({title: element.title, ref: element.ref});
        });
      },
      err => {
        console.log("Error");
      }
    );
  }
  
  getSaves(): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': '*/*' })
    };

    return this.http.get(environment.apiUrl + 'save', httpOptions);
  }

}
