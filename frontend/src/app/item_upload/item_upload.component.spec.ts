import { HttpClientTestingModule } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { RouterTestingModule } from '@angular/router/testing';

import { ItemUploadComponent } from './item_upload.component';

xdescribe('ItemUploadComponent', () => {
  let component: ItemUploadComponent;
  let fixture: ComponentFixture<ItemUploadComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ItemUploadComponent, HttpClientTestingModule, RouterTestingModule]
    })
      .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ItemUploadComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
