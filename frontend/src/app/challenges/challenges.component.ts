import { Component, OnInit } from '@angular/core';
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { TokenStorageService } from '../_services/token-storage.service';
import { environment } from '../../environments/environment';

@Component({
  selector: 'app-challenges',
  templateUrl: './challenges.component.html',
  styleUrls: ['./challenges.component.css']
})
export class ChallengesComponent implements OnInit {
  challenges: any[] = [];

  constructor(private token: TokenStorageService, private http: HttpClient) { }

  ngOnInit(): void {
      this.getChallenges().subscribe(
      data => {
        data.forEach((element: any) => {
            this.challenges.push({title: element.title, ref: element.ref});
        });
      },
      err => {
        console.log("Error");
      }
    );
  }
  
  getChallenges(): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': '*/*' })
    };

    return this.http.get(environment.apiUrl + 'challenge', httpOptions);
  }

}
