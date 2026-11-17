# Configuração da autenticação

Esta área usa PHP com MySQL/MariaDB. No XAMPP, inicia Apache e MySQL antes de configurar.

1. No phpMyAdmin, importa `auth-schema.sql` para criar a base de dados, as contas, as marcações e as configurações do ginásio. Se já tinhas importado o esquema antigo, importa também `pap-upgrade.sql`.
2. Cria a pasta privada `C:\xampp\pap-private-data`, fora da pasta pública `htdocs`.
3. Copia `auth-config.example.php` para `C:\xampp\pap-private-data\auth-config.php` e ajusta o utilizador e a palavra-passe MySQL. O ficheiro privado contém credenciais e não deve ser publicado.
4. No PowerShell, cria primeiro a conta de administrador:

   ```powershell
   powershell -ExecutionPolicy Bypass -File C:\xampp\htdocs\pap\create-admin.ps1
   ```

   A palavra-passe é pedida sem aparecer no terminal e deve ter pelo menos 12 caracteres. Não a partilhes por email.
5. Para criar contas de clientes, usa:

   ```powershell
   powershell -ExecutionPolicy Bypass -File C:\xampp\htdocs\pap\create-client.ps1
   ```

   Introduz nome, email e uma palavra-passe inicial com pelo menos 12 caracteres. A palavra-passe é pedida sem aparecer no terminal; comunica-a ao cliente por um canal seguro.
6. O administrador inicia sessão em `login.php` e é encaminhado para `admin.php`. Os clientes são encaminhados para `area-cliente.php`, onde podem consultar e cancelar as suas marcações futuras. Não existe registo público.
7. Em `admin-config.php`, configura telefone, morada e preços reais. Os pedidos de marcação são guardados na tabela `bookings`; o email é apenas uma notificação e depende da configuração SMTP do XAMPP.
8. A página `privacidade.php` é um modelo inicial para o projeto. Antes de publicar, confirma a identificação legal do responsável, a morada e os prazos de conservação dos dados.

As marcações antigas guardadas em `C:\xampp\pap-private-data\horarios.jsonl` não são importadas automaticamente; conserva esse ficheiro como arquivo.

O exemplo de configuração usa os valores de desenvolvimento comuns do XAMPP (`root` sem palavra-passe). Isto não é adequado para um servidor público: cria um utilizador MySQL próprio, define uma palavra-passe forte, configura HTTPS e limita o acesso à pasta privada antes de publicar o site.
