const fs = require("fs");
const https = require("https");
const next = require("next");
const path = require("path");
const child_process = require("child_process");

// Next.js config
const dev = process.env.NODE_ENV !== "production";
const app = next({ dev });
const handle = app.getRequestHandler();

// Pasta de certificados do ASP.NET Core
const baseFolder =
  process.env.APPDATA && process.env.APPDATA !== ""
    ? `${process.env.APPDATA}/ASP.NET/https`
    : `${process.env.HOME}/.aspnet/https`;

const certificateName = process.env.npm_package_name;
const certPath = path.join(baseFolder, `${certificateName}.pem`);
const keyPath = path.join(baseFolder, `${certificateName}.key`);

// Se os certificados não existirem, gerar automaticamente
if (!fs.existsSync(certPath) || !fs.existsSync(keyPath)) {
  const result = child_process.spawnSync(
    "dotnet",
    [
      "dev-certs",
      "https",
      "--export-path",
      certPath,
      "--format",
      "Pem",
      "--no-password",
    ],
    { stdio: "inherit" }
  );

  if (result.status !== 0) {
    throw new Error("Falha ao gerar certificado HTTPS");
  }
}

// Inicializar Next dentro de um servidor HTTPS
app.prepare().then(() => {
  https
    .createServer(
      {
        key: fs.readFileSync(keyPath),
        cert: fs.readFileSync(certPath),
      },
      (req, res) => handle(req, res)
    )
    .listen(3000, "0.0.0.0", () => {
      console.log("Servidor HTTPS rodando em https://localhost:3000");
    });
});
