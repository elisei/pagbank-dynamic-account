# O2TI - Dynamic Account

Módulo que altera aleatoriamente a conta utilizada para o processamento do pagamento durante o checkout.

Com esse módulo é distribuido de forma aleatória o pagamento de uma transação com a decisão da conta que receberá feita via sorteio das contas cadastradas e habilitadas para o pagamento.

Apenas quando a transação for feita via cartão salvo (vault) é a que esse fluxo deixa de ser usado recuperando a conta original que salvo o cartão inicialmente.

É criada uma nova seção na configuração de pagamento para capturar novas autenticações:
![image da configuração](https://github.com/elisei/pagbank-dynamic-account/assets/1786389/bac5180e-fea3-4389-8637-5f7af504442b)

O administrador deverá clicar em "Connect new Account" e seguir o fluxo de autorização comum ao módulo.

## Instalação

Via composer

```ssh
composer require o2ti/pagbank-dynamic-account
```

Após a instalação pelo Composer, execute os seguintes comandos:

```sh
bin/magento setup:upgrade
bin/magento setup:di:compile
```

## Advertência
O período de validade da autenticação é de 1 ano, nesse módulo não há regeneração das contas, você pode utilizar o valor salvo em refresh_token para gerar nova credencial e cadastrar manualmente antes desse período, [siga as orientações da documentação do PagBank](https://dev.pagbank.uol.com.br/reference/renovar-access-token) para esse processo.


## Resalvas

A O2TI e seus desenvolvedores não se responsabilizam pelo uso do módulo, por favor fazam testes e caso necessário abram um issue conosco!