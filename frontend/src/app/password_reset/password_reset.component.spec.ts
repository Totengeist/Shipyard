import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute } from '@angular/router';
import { RouterTestingModule } from '@angular/router/testing';
import { Observable, of } from 'rxjs';

import { PasswordResetComponent } from './password_reset.component';

describe('ProfileComponent', () => {
  let component: PasswordResetComponent;
  let fixture: ComponentFixture<PasswordResetComponent>;
  let routeStub: {params: Observable<Record<string,string>>|null};

  beforeEach(async () => {
    routeStub = {params: null};

    await TestBed.configureTestingModule({
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        {
          provide: ActivatedRoute,
          useValue: routeStub
        },
      ],
      imports: [
        PasswordResetComponent,
        RouterTestingModule,
      ]
    })
      .compileComponents();

    fixture = TestBed.createComponent(PasswordResetComponent);
    component = fixture.componentInstance;
  });

  it('should create', () => {
    routeStub.params = of({});

    fixture.detectChanges();
    fixture.whenStable().then(() => {
      expect(component).toBeTruthy();
    });
  });

  it('should request a reset', () => {
    routeStub.params = of({});

    fixture.detectChanges();
    fixture.whenStable().then(() => {
      component.onSubmit();
      expect(component.token).toEqual('');
      expect(component.email).not.toEqual(null);
      expect(component.password).toEqual(null);
      expect(component.passwordConfirmation).toEqual(null);
    });
  });

  it('should send a reset', () => {
    routeStub.params = of({
      token: 'testing-token',
    });

    fixture.detectChanges();
    fixture.whenStable().then(() => {
      component.onSubmit();
      expect(component.token).toEqual('testing-token');
      expect(component.email).toEqual(null);
      expect(component.password).not.toEqual(null);
      expect(component.passwordConfirmation).not.toEqual(null);
    });
  });
});
