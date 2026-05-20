package com.example.replyapp.api

import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.PUT
import retrofit2.http.Path

data class OsRequest(
    val descricao_item: String,
    val modelo: String,
    val defeito_relatado: String,
    val data_pronto: String,
    val nome: String,
    val telefone: String,
    val valor: Double,
    val status_pagamento: String,
    val valor_pago: Double
)

data class StatusRequest(
    val status: String
)

data class Cliente(
    val id: Int,
    val nome: String,
    val telefone: String
)

data class OrdemServico(
    val id: Int,
    val descricao_item: String,
    val modelo: String?,
    val defeito_relatado: String?,
    val data_pronto: String?,
    val status: String,
    val valor_orcamento: Double,
    val status_pagamento: String,
    val valor_pago: Double,
    val cliente: Cliente?,
    val data_entrada: String?
)

interface ApiService {
    @GET("ordens-servico")
    suspend fun getOrdensServico(): List<OrdemServico>

    @POST("ordens-servico")
    suspend fun createOrdemServico(@Body request: OsRequest): Any

    @PUT("ordens-servico/{id}")
    suspend fun updateOrdemServico(@Path("id") id: Int, @Body request: OsRequest): Any

    @PUT("ordens-servico/{id}/status")
    suspend fun updateStatus(@Path("id") id: Int, @Body request: StatusRequest): Any
}

object RetrofitClient {
    private const val BASE_URL = "http://191.252.101.239:9080/api/"

    val instance: ApiService by lazy {
        val retrofit = Retrofit.Builder()
            .baseUrl(BASE_URL)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
        retrofit.create(ApiService::class.java)
    }
}
