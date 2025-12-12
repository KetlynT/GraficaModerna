const fs = require("fs");
const path = require("path");
const child_process = require("child_process");

const baseFolder =
  process.env.APPDATA && process.env.APPDATA !== ""
    ? `${process.env.APPDATA}/ASP.NET/https`
    : `${process.env.HOME}/.aspnet/https`;

if (!fs.existsSync(baseFolder)) {
  fs.mkdirSync(baseFolder, { recursive: true });
}

const certificateName = process.env.npm_package_name;
const certFilePath = path.join(baseFolder, `${certificateName}.pem`);
const keyFilePath = path.join(baseFolder, `${certificateName}.key`);

if (!fs.existsSync(certFilePath) || !fs.existsSync(keyFilePath)) {
  const result = child_process.spawnSync(
    "dotnet",
    [
      "dev-certs",
      "https",
      "--export-path",
      certFilePath,
      "--format",
      "Pem",
      "--no-password",
    ],
    { stdio: "inherit" }
  );

  if (result.status !== 0) {
    throw new Error("Não foi possível gerar o certificado HTTPS.");
  }
}

module.exports = {
  devIndicators: { buildActivity: false },

  server: {
    https: {
      key: fs.readFileSync(keyFilePath),
      cert: fs.readFileSync(certFilePath),
    },
    port: 3000,
    host: "localhost"
  }
};