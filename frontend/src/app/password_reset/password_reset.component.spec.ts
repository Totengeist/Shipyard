import { HttpErrorResponse } from '@angular/common/http';
import { ComponentFixture, TestBed, fakeAsync } from '@angular/core/testing';
import { ActivatedRoute, provideRouter } from '@angular/router';

import { Observable, of, throwError } from 'rxjs';

import { ApiService } from '../_services/api.service';
import { PasswordResetComponent } from './password_reset.component';

describe('ProfileComponent', () => {
  let component: PasswordResetComponent;
  let fixture: ComponentFixture<PasswordResetComponent>;
  let routeStub: {params: Observable<Record<string,string>>|null};
  const apiServiceMock = {
    post: jest.fn(),
    get: jest.fn(),
  }

  beforeEach(async () => {
    routeStub = {params: null};

    await TestBed.configureTestingModule({
      providers: [
        { provide: ApiService, useValue: apiServiceMock },
        provideRouter([]),
        {
          provide: ActivatedRoute,
          useValue: routeStub
        },
      ],
      imports: [
        PasswordResetComponent,
      ]
    })
      .compileComponents();

    fixture = TestBed.createComponent(PasswordResetComponent);
    component = fixture.componentInstance;
  });

  it('should create', fakeAsync(async () => {
    routeStub.params = of({});

    fixture.detectChanges();
    await fixture.whenStable()
    expect(component).toBeTruthy();
  }));

  it('should request a reset', fakeAsync(async () => {
    routeStub.params = of({});

    fixture.detectChanges();
    apiServiceMock.post.mockReturnValue(of([]));
    component.onSubmit();
    await fixture.whenStable();

    fixture.detectChanges();
    expect(component.token).toEqual('');
    expect(component.email).not.toEqual(null);
    expect(component.password).toEqual(null);
    expect(component.passwordConfirmation).toEqual(null);
    expect(component.submit!.disabled).toBe(true);
    expect(component.error).toBe(false);
  }));

  it('should gracefully fail when requesting a reset', fakeAsync(async () => {
    routeStub.params = of({});

    fixture.detectChanges();
    apiServiceMock.post.mockReturnValue(throwError(new HttpErrorResponse({status: 404})));
    component.onSubmit();
    await fixture.whenStable();

    fixture.detectChanges();
    expect(component.token).toEqual('');
    expect(component.email).not.toEqual(null);
    expect(component.password).toEqual(null);
    expect(component.passwordConfirmation).toEqual(null);
    expect(component.submit!.disabled).toBe(false);
    expect(component.error).toBe(true);
  }));

  it('should send a reset', fakeAsync(async () => {
    routeStub.params = of({token: 'testing-token'});

    fixture.detectChanges();
    apiServiceMock.post.mockReturnValue(of([]));
    component.onSubmit();
    await fixture.whenStable();

    fixture.detectChanges();
    expect(component.token).toEqual('testing-token');
    expect(component.email).toEqual(null);
    expect(component.password).not.toEqual(null);
    expect(component.passwordConfirmation).not.toEqual(null);
    expect(component.submit!.disabled).toBe(true);
    expect(component.error).toBe(false);
  }));

  it('should gracefully fail when sending a reset', fakeAsync(async () => {
    routeStub.params = of({token: 'testing-token'});

    fixture.detectChanges();
    apiServiceMock.post.mockReturnValue(throwError(new HttpErrorResponse({status: 404})));
    component.onSubmit();
    await fixture.whenStable();

    fixture.detectChanges();
    expect(component.token).toEqual('testing-token');
    expect(component.email).toEqual(null);
    expect(component.password).not.toEqual(null);
    expect(component.passwordConfirmation).not.toEqual(null);
    expect(component.submit!.disabled).toBe(false);
    expect(component.error).toBe(true);
  }));
});
