export const cleanString = (str) => {
  if (!str) return '';
  return str.replace(/\D/g, '');
};

export const maskCpfCnpj = (value) => {
  const cleanValue = cleanString(value);
  
  if (cleanValue.length <= 11) {
    // Máscara CPF: 000.000.000-00
    return cleanValue
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d{1,2})/, '$1-$2')
      .replace(/(-\d{2})\d+?$/, '$1');
  } else {
    // Máscara CNPJ: 00.000.000/0000-00
    return cleanValue
      .replace(/^(\d{2})(\d)/, '$1.$2')
      .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
      .replace(/\.(\d{3})(\d)/, '.$1/$2')
      .replace(/(\d{4})(\d)/, '$1-$2')
      .replace(/(-\d{2})\d+?$/, '$1'); 
  }
};

export const maskPhone = (value) => {
  const cleanValue = cleanString(value);
  
  // Máscara Telefone: (00) 00000-0000
  return cleanValue
    .replace(/\D/g, "")
    .replace(/^(\d{2})(\d)/g, "($1) $2")
    .replace(/(\d)(\d{4})$/, "$1-$2")
    .substring(0, 15); // Limita tamanho
};