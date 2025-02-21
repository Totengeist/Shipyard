import { HttpClientTestingModule } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { RouterTestingModule } from '@angular/router/testing';

import { MarkdownComponent, MarkdownModule } from 'ngx-markdown';

import { ItemPageComponent } from './item_page.component';

describe('ItemPageComponent', () => {
  let component: ItemPageComponent;
  let fixture: ComponentFixture<ItemPageComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ItemPageComponent, HttpClientTestingModule, RouterTestingModule, MarkdownModule.forRoot(), MarkdownComponent]
    })
      .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ItemPageComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
