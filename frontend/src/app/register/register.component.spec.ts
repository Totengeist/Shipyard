import { HttpErrorResponse } from '@angular/common/http';
import { ComponentFixture, TestBed, fakeAsync } from '@angular/core/testing';

import { of, throwError } from 'rxjs';

import { AuthService } from '../_services/auth.service';
import { RegisterComponent } from './register.component';

describe('RegisterComponent', () => {
  let component: RegisterComponent;
  let fixture: ComponentFixture<RegisterComponent>;
  const authServiceMock = {
    register: jest.fn(),
  }

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      providers: [
        { provide: AuthService, useValue: authServiceMock },
      ],
      imports: [
        RegisterComponent,
      ]
    })
      .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(RegisterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should register a user', fakeAsync(async () => {
    component.form = {
      name: 'Test User',
      email: 'test@tls-wiki.com',
      password: 'secret',
      password_confirmation: 'secret'
    }

    fixture.detectChanges();
    authServiceMock.register.mockReturnValue(of({'user': {
      'name': 'Test User'
    }}));
    component.onSubmit();
    await fixture.whenStable();

    fixture.detectChanges();
    expect(component.errorMessage).toEqual('');
    expect(component.isSuccessful).toBe(true);
    expect(component.isSignUpFailed).toBe(false);
  }));

  it('should gracefully fail when registering a user', fakeAsync(async () => {
    component.form = {
      name: 'Test User',
      email: 'test@tls-wiki.com',
      password: 'secret',
      password_confirmation: 'secret2'
    }

    fixture.detectChanges();
    authServiceMock.register.mockReturnValue(throwError(new HttpErrorResponse({
      status: 404,
      error: {
        errors: {
          email: ['Email is not unique.'],
          password: ['Password must be the same as \'password_confirmation\''],
        }
      }
    })));
    component.onSubmit();
    await fixture.whenStable();

    fixture.detectChanges();
    expect(component.errorMessage).toEqual('Email is not unique. Password must be the same as \'password_confirmation\'');
    expect(component.isSuccessful).toBe(false);
    expect(component.isSignUpFailed).toBe(true);
  }));
});
