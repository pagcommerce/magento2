<img src="https://avatars.githubusercontent.com/u/128087947?s=400&u=f71900b26de86f2942b40c8c4e9d0d70df39b3d8&v=4&s=200" width="127px" height="127px" align="left"/>

# Pagcommerce Magento 2

Módulo de integração Pagcommerce para Magento 2.x

<br>

## Requisitos

- [Magento Community](https://magento.com/products/community-edition) 2.x/
- [PHP](http://php.net) >= 7.2.x
- Cron

## Instalação

### composer

```
composer require pagcommerce/magento2
```
```
composer update
```

```
bin/magento setup:di:compile
```

```
bin/magento setup:static-content:deploy -f 
```

Limpe o cache em `Sistema > Ferramentas - Gerenciamento de Cache`


## Configuração

1. Acesse o painel administrativo da sua loja
2. Vá em `Lojas > Configuração > Vendas > Formas de Pagamento > Outros Meios de Pagamento > Pagcommerce - Configurações Gerais`
3. Altere o **Modo** para **Produção**
4. Informe sua **Api_Key** e sua **Api_Secret** - Caso não tenha as chaves, acesse nosso site e clique no menu **"Configurações - Chaves de API"**
5. Salve as configurações
6. Ative o PIX na sessão "Pagcommerce PIX"
7. Ative o Boleto na sessão "Pagcommerce Boleto"

### Configuração de cancelamento automático de boletos não pagos

Pedidos que forem criados na plataforma com boleto oi PIX como forma de pagamento,
deverão ser cancelados após o vencimento. O módulo possui um processo
automatizado que, identifica os boletos pendentes e, se em **15** dias após a
data de vencimento não houver o pagamento, o pedido é **cancelado**.
