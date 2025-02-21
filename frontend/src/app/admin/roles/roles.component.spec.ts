import { HttpClientTestingModule } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AdminRolesComponent } from './roles.component';

describe('AdminRolesComponent', () => {
  let component: AdminRolesComponent;
  let fixture: ComponentFixture<AdminRolesComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AdminRolesComponent, HttpClientTestingModule]
    })
      .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(AdminRolesComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
