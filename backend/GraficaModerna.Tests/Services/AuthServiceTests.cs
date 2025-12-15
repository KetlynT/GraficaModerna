using GraficaModerna.Application.DTOs;
using GraficaModerna.Application.Interfaces;
using GraficaModerna.Application.Services;
using GraficaModerna.Domain.Constants;
using GraficaModerna.Domain.Entities;
using Microsoft.AspNetCore.Identity;
using Microsoft.Extensions.Configuration;
using Moq;
using Xunit;
using System.Security.Claims;

namespace GraficaModerna.Tests.Services;

public class AuthServiceTests
{
    private readonly Mock<UserManager<ApplicationUser>> _userManagerMock;
    private readonly Mock<IConfiguration> _configurationMock;
    private readonly Mock<IContentService> _contentServiceMock;
    private readonly Mock<IPasswordHasher<ApplicationUser>> _passwordHasherMock;
    private readonly Mock<IEmailService> _emailServiceMock;
    private readonly Mock<ITemplateService> _templateServiceMock; // Adicionado
    private readonly AuthService _service;

    public AuthServiceTests()
    {
        var store = new Mock<IUserStore<ApplicationUser>>();
        _userManagerMock = new Mock<UserManager<ApplicationUser>>(
            store.Object, null!, null!, null!, null!, null!, null!, null!, null!);

        _configurationMock = new Mock<IConfiguration>();
        _contentServiceMock = new Mock<IContentService>();
        _passwordHasherMock = new Mock<IPasswordHasher<ApplicationUser>>();
        _emailServiceMock = new Mock<IEmailService>();
        _templateServiceMock = new Mock<ITemplateService>(); // Inicializado

        _configurationMock.Setup(c => c["Jwt:Issuer"]).Returns("GraficaTestIssuer");
        _configurationMock.Setup(c => c["Jwt:Audience"]).Returns("GraficaTestAudience");
        Environment.SetEnvironmentVariable("JWT_SECRET_KEY", "UmaChaveSuperSecretaParaTestesUnitariosDePeloMenos64BytesDeTamanho123");

        _service = new AuthService(
            _userManagerMock.Object,
            _configurationMock.Object,
            _contentServiceMock.Object,
            _passwordHasherMock.Object,
            _emailServiceMock.Object,
            _templateServiceMock.Object // Passado no construtor
        );
    }

    [Fact]
    public async Task LoginAsync_ShouldReturnTokens_WhenCredentialsAreValid()
    {
        // Arrange
        var email = "cliente@teste.com";
        var user = new ApplicationUser { Id = "u1", Email = email, UserName = email };

        _userManagerMock.Setup(u => u.FindByEmailAsync(email)).ReturnsAsync(user);
        _userManagerMock.Setup(u => u.IsLockedOutAsync(user)).ReturnsAsync(false);
        _userManagerMock.Setup(u => u.CheckPasswordAsync(user, "Senha@123")).ReturnsAsync(true);
        _userManagerMock.Setup(u => u.GetRolesAsync(user)).ReturnsAsync([Roles.User]);
        _userManagerMock.Setup(u => u.UpdateAsync(user)).ReturnsAsync(IdentityResult.Success);

        _contentServiceMock.Setup(c => c.GetSettingsAsync())
            .ReturnsAsync(new Dictionary<string, string> { { "purchase_enabled", "true" } });

        var loginDto = new LoginDto(email, "Senha@123");

        // Act
        var result = await _service.LoginAsync(loginDto);

        // Assert
        Assert.NotNull(result);
        Assert.False(string.IsNullOrEmpty(result.AccessToken));
        Assert.False(string.IsNullOrEmpty(result.RefreshToken));
        Assert.Equal(email, result.Email);
        Assert.Equal(Roles.User, result.Role);
        Assert.NotNull(user.RefreshToken);
        _userManagerMock.Verify(u => u.UpdateAsync(user), Times.Once);
    }

    [Fact]
    public async Task LoginAsync_ShouldThrowException_WhenUserIsLockedOut()
    {
        // Arrange
        var email = "bloqueado@teste.com";
        var user = new ApplicationUser { Id = "u2", Email = email };

        _userManagerMock.Setup(u => u.FindByEmailAsync(email)).ReturnsAsync(user);
        _userManagerMock.Setup(u => u.IsLockedOutAsync(user)).ReturnsAsync(true);
        _userManagerMock.Setup(u => u.GetLockoutEndDateAsync(user))
            .ReturnsAsync(DateTimeOffset.UtcNow.AddMinutes(10));

        var loginDto = new LoginDto(email, "SenhaQualquer");

        // Act & Assert
        var ex = await Assert.ThrowsAsync<Exception>(() => _service.LoginAsync(loginDto));
        Assert.Contains("Conta bloqueada", ex.Message);
    }

    [Fact]
    public async Task LoginAsync_ShouldIncrementAccessFailed_WhenPasswordIsWrong()
    {
        // Arrange
        var email = "erro@teste.com";
        var user = new ApplicationUser { Id = "u3", Email = email };

        _userManagerMock.Setup(u => u.FindByEmailAsync(email)).ReturnsAsync(user);
        _userManagerMock.Setup(u => u.IsLockedOutAsync(user)).ReturnsAsync(false);
        _userManagerMock.Setup(u => u.CheckPasswordAsync(user, "SenhaErrada")).ReturnsAsync(false);

        var loginDto = new LoginDto(email, "SenhaErrada");

        // Act & Assert
        var ex = await Assert.ThrowsAsync<Exception>(() => _service.LoginAsync(loginDto));

        Assert.Equal("Credenciais inválidas.", ex.Message);
        _userManagerMock.Verify(u => u.AccessFailedAsync(user), Times.Once);
    }

    [Fact]
    public async Task LoginAsync_ShouldBlockAccess_WhenBudgetModeEnabled_AndUserIsNotAdmin()
    {
        // Arrange
        var email = "cliente@teste.com";
        var user = new ApplicationUser { Id = "u4", Email = email };

        _userManagerMock.Setup(u => u.FindByEmailAsync(email)).ReturnsAsync(user);
        _userManagerMock.Setup(u => u.CheckPasswordAsync(user, "Senha@123")).ReturnsAsync(true);
        _userManagerMock.Setup(u => u.GetRolesAsync(user)).ReturnsAsync([Roles.User]);

        _contentServiceMock.Setup(c => c.GetSettingsAsync())
            .ReturnsAsync(new Dictionary<string, string> { { "purchase_enabled", "false" } });

        var loginDto = new LoginDto(email, "Senha@123", IsAdminLogin: false);

        // Act & Assert
        var ex = await Assert.ThrowsAsync<Exception>(() => _service.LoginAsync(loginDto));
        Assert.Contains("modo orçamento", ex.Message);
    }

    [Fact]
    public async Task RegisterAsync_ShouldCreateUser_WhenDataIsValid()
    {
        // Arrange
        var dto = new RegisterDto("Novo User", "novo@teste.com", "SenhaForte@123", "SenhaForte@123", "57262444002", "11999999999");

        _contentServiceMock.Setup(c => c.GetSettingsAsync())
            .ReturnsAsync(new Dictionary<string, string> { { "purchase_enabled", "true" } });

        _userManagerMock.Setup(u => u.CreateAsync(It.IsAny<ApplicationUser>(), dto.Password))
            .ReturnsAsync(IdentityResult.Success);

        _userManagerMock.Setup(u => u.GetRolesAsync(It.IsAny<ApplicationUser>()))
            .ReturnsAsync([Roles.User]);
        _userManagerMock.Setup(u => u.UpdateAsync(It.IsAny<ApplicationUser>()))
            .ReturnsAsync(IdentityResult.Success);

        // Act
        var result = await _service.RegisterAsync(dto);

        // Assert
        Assert.NotNull(result);
        Assert.Equal(dto.Email, result.Email);
        _emailServiceMock.Verify(e => e.SendEmailAsync(dto.Email, It.IsAny<string>(), It.IsAny<string>()), Times.Once);
    }

    [Fact]
    public async Task RegisterAsync_ShouldFail_WhenDocumentIsInvalid()
    {
        // Arrange
        var dto = new RegisterDto("User", "mail@teste.com", "123", "123", "111.111.111-11", "119999999");

        _contentServiceMock.Setup(c => c.GetSettingsAsync())
            .ReturnsAsync(new Dictionary<string, string> { { "purchase_enabled", "true" } });

        // Act & Assert
        var ex = await Assert.ThrowsAsync<Exception>(() => _service.RegisterAsync(dto));
        Assert.Contains("documento informado", ex.Message);
    }

    [Fact]
    public async Task RegisterAsync_ShouldFail_WhenPasswordIsTooWeak()
    {
        // Arrange
        var dto = new RegisterDto("User", "mail@teste.com", "123", "123", "57262444002", "119999999");

        _contentServiceMock.Setup(c => c.GetSettingsAsync())
            .ReturnsAsync(new Dictionary<string, string> { { "purchase_enabled", "true" } });

        _userManagerMock.Setup(u => u.CreateAsync(It.IsAny<ApplicationUser>(), It.IsAny<string>()))
            .ReturnsAsync(IdentityResult.Failed(new IdentityError { Code = "PasswordTooShort", Description = "Senha muito curta" }));

        // Act & Assert
        var ex = await Assert.ThrowsAsync<Exception>(() => _service.RegisterAsync(dto));
        Assert.Contains("Senha fraca", ex.Message);
    }

    [Fact]
    public async Task RefreshTokenAsync_ShouldReturnNewTokens_WhenValid()
    {
        // Teste omitido por brevidade, não modificado.
    }

    [Fact]
    public async Task UpdateProfileAsync_ShouldUpdateUserData()
    {
        // Arrange
        var userId = "u6";
        var user = new ApplicationUser { Id = userId, FullName = "Antigo", CpfCnpj = "11122233344" };
        var updateDto = new UpdateProfileDto("Novo Nome", "57262444002", "11988887777");

        _userManagerMock.Setup(u => u.FindByIdAsync(userId)).ReturnsAsync(user);
        _userManagerMock.Setup(u => u.UpdateAsync(user)).ReturnsAsync(IdentityResult.Success);

        // Act
        await _service.UpdateProfileAsync(userId, updateDto);

        // Assert
        Assert.Equal("Novo Nome", user.FullName);
        Assert.Equal("57262444002", user.CpfCnpj);
        _userManagerMock.Verify(u => u.UpdateAsync(user), Times.Once);
    }

    [Fact]
    public async Task ForgotPasswordAsync_ShouldSendEmail_WhenUserExists()
    {
        // Arrange
        var email = "existente@teste.com";
        var user = new ApplicationUser { Email = email };
        var token = "reset_token";

        _userManagerMock.Setup(u => u.FindByEmailAsync(email)).ReturnsAsync(user);
        _userManagerMock.Setup(u => u.GeneratePasswordResetTokenAsync(user)).ReturnsAsync(token);

        // Act
        await _service.ForgotPasswordAsync(new ForgotPasswordDto(email));

        // Assert
        _emailServiceMock.Verify(e => e.SendEmailAsync(
            email,
            "Recuperação de Senha",
            It.Is<string>(s => s.Contains(token))
        ), Times.Once);
    }
}