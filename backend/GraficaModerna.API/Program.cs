using GraficaModerna.Application.Interfaces;
using GraficaModerna.Application.Mappings;
using GraficaModerna.Application.Services;
using GraficaModerna.Application.Validators;
using GraficaModerna.Domain.Entities;
using GraficaModerna.Domain.Interfaces;
using GraficaModerna.Infrastructure.Context;
using GraficaModerna.Infrastructure.Repositories;
using FluentValidation;
using FluentValidation.AspNetCore;
using Microsoft.AspNetCore.Authentication.JwtBearer;
using Microsoft.AspNetCore.Identity;
using Microsoft.EntityFrameworkCore;
using Microsoft.IdentityModel.Tokens;
using Microsoft.OpenApi.Models;
using System.Text;

var builder = WebApplication.CreateBuilder(args);

// ==========================================
// 1. CONFIGURAÇÃO DE BANCO DE DADOS (POSTGRES)
// ==========================================
builder.Services.AddDbContext<AppDbContext>(options =>
    options.UseNpgsql(builder.Configuration.GetConnectionString("DefaultConnection")));

// ==========================================
// 2. IDENTITY (USUÁRIOS E ROLES)
// ==========================================
builder.Services.AddIdentity<ApplicationUser, IdentityRole>(options =>
{
    // Configurações de senha para DEV (pode deixar mais rígido em PROD)
    options.Password.RequireDigit = false;
    options.Password.RequireLowercase = false;
    options.Password.RequireNonAlphanumeric = false;
    options.Password.RequireUppercase = false;
    options.Password.RequiredLength = 6;
})
.AddEntityFrameworkStores<AppDbContext>()
.AddDefaultTokenProviders();

// ==========================================
// 3. AUTENTICAÇÃO JWT
// ==========================================
var key = Encoding.ASCII.GetBytes(builder.Configuration["Jwt:Key"]!);

builder.Services.AddAuthentication(options =>
{
    options.DefaultAuthenticateScheme = JwtBearerDefaults.AuthenticationScheme;
    options.DefaultChallengeScheme = JwtBearerDefaults.AuthenticationScheme;
})
.AddJwtBearer(options =>
{
    options.RequireHttpsMetadata = false;
    options.SaveToken = true;
    options.TokenValidationParameters = new TokenValidationParameters
    {
        ValidateIssuerSigningKey = true,
        IssuerSigningKey = new SymmetricSecurityKey(key),
        ValidateIssuer = true,
        ValidIssuer = builder.Configuration["Jwt:Issuer"],
        ValidateAudience = true,
        ValidAudience = builder.Configuration["Jwt:Audience"],
        ValidateLifetime = true
    };
});

// ==========================================
// 4. INJEÇÃO DE DEPENDÊNCIA (DI)
// ==========================================
// Application
builder.Services.AddScoped<IProductService, ProductService>();
builder.Services.AddScoped<IAuthService, AuthService>();

// Infrastructure
builder.Services.AddScoped<IProductRepository, ProductRepository>();

// Ferramentas
builder.Services.AddAutoMapper(typeof(DomainMappingProfile));
builder.Services.AddValidatorsFromAssemblyContaining<CreateProductValidator>();

// ==========================================
// 5. API E SWAGGER (COM SUPORTE A JWT)
// ==========================================
builder.Services.AddControllers();
builder.Services.AddEndpointsApiExplorer();

builder.Services.AddSwaggerGen(c =>
{
    c.SwaggerDoc("v1", new OpenApiInfo { Title = "Grafica A Moderna API", Version = "v1" });

    // Configuração para aparecer o cadeado no Swagger
    c.AddSecurityDefinition("Bearer", new OpenApiSecurityScheme
    {
        Description = "Insira o token JWT desta maneira: Bearer {seu token}",
        Name = "Authorization",
        In = ParameterLocation.Header,
        Type = SecuritySchemeType.ApiKey,
        Scheme = "Bearer"
    });

    c.AddSecurityRequirement(new OpenApiSecurityRequirement()
    {
        {
            new OpenApiSecurityScheme
            {
                Reference = new OpenApiReference
                {
                    Type = ReferenceType.SecurityScheme,
                    Id = "Bearer"
                },
                Scheme = "oauth2",
                Name = "Bearer",
                In = ParameterLocation.Header,
            },
            new List<string>()
        }
    });
});

// ==========================================
// 6. CORS (PERMITIR FRONTEND)
// ==========================================
builder.Services.AddCors(options =>
{
    options.AddPolicy("AllowFrontend",
        b => b.WithOrigins("http://localhost:5173") // URL do Vite
              .AllowAnyHeader()
              .AllowAnyMethod());
});

var app = builder.Build();

// ==========================================
// PIPELINE DE EXECUÇÃO (MIDDLEWARES)
// ==========================================

// Swagger
if (app.Environment.IsDevelopment())
{
    app.UseSwagger();
    app.UseSwaggerUI();
}

// Arquivos Estáticos (Imagens)
app.UseStaticFiles();

// CORS
app.UseCors("AllowFrontend");

// Auth
app.UseAuthentication(); // Quem é você?
app.UseAuthorization();  // O que você pode fazer?

// Controllers
app.MapControllers();

app.Run();