package com.example.replyapp.ui

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import com.example.replyapp.api.OrdemServico
import com.example.replyapp.api.RetrofitClient
import com.example.replyapp.api.StatusRequest
import kotlinx.coroutines.launch

class HomeViewModel : ViewModel() {
    var ordens by mutableStateOf<List<OrdemServico>>(emptyList())
        private set

    fun loadOrdens() {
        viewModelScope.launch {
            try {
                ordens = RetrofitClient.instance.getOrdensServico()
            } catch (e: Exception) {
                e.printStackTrace()
            }
        }
    }

    fun updateStatus(id: Int, newStatus: String) {
        viewModelScope.launch {
            try {
                RetrofitClient.instance.updateStatus(id, StatusRequest(newStatus))
                loadOrdens()
            } catch (e: Exception) {
                e.printStackTrace()
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HomeScreen(
    onNavigateToCreate: () -> Unit,
    onNavigateToEdit: (OrdemServico) -> Unit,
    onNavigateToSearch: () -> Unit,
    viewModel: HomeViewModel = viewModel()
) {
    LaunchedEffect(Unit) {
        viewModel.loadOrdens()
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("ReplyApp - Ordens de Serviço") },
                actions = {
                    IconButton(onClick = onNavigateToSearch) {
                        Icon(Icons.Default.Search, contentDescription = "Pesquisar")
                    }
                    IconButton(onClick = { viewModel.loadOrdens() }) {
                        Icon(Icons.Default.Refresh, contentDescription = "Atualizar")
                    }
                }
            )
        },
        floatingActionButton = {
            FloatingActionButton(onClick = onNavigateToCreate) {
                Icon(Icons.Default.Add, contentDescription = "Nova OS")
            }
        }
    ) { padding ->
        LazyColumn(contentPadding = padding, modifier = Modifier.fillMaxSize()) {
            items(viewModel.ordens) { os ->
                OsItemCard(
                    os = os,
                    onNavigateToEdit = onNavigateToEdit,
                    onUpdateStatus = { status -> viewModel.updateStatus(os.id, status) }
                )
            }
        }
    }
}

@Composable
fun OsItemCard(
    os: OrdemServico,
    onNavigateToEdit: (OrdemServico) -> Unit,
    onUpdateStatus: (String) -> Unit
) {
    Card(modifier = Modifier.fillMaxWidth().padding(8.dp)) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(text = "OS #${os.id} - ${os.cliente?.nome}", style = MaterialTheme.typography.titleMedium)
            Text(text = "Modelo: ${os.modelo ?: os.descricao_item ?: ""}")
            Text(text = "Status: ${os.status}", color = MaterialTheme.colorScheme.primary)
            
            val valorFormatado = String.format(java.util.Locale("pt", "BR"), "%.2f", os.valor_orcamento)
            val paymentText = when (os.status_pagamento) {
                "total" -> "R$ $valorFormatado PAGO"
                "parcial" -> {
                    val deve = os.valor_orcamento - os.valor_pago
                    "R$ $valorFormatado (DEVE R$ ${String.format(java.util.Locale("pt", "BR"), "%.2f", deve)})"
                }
                else -> "R$ $valorFormatado" // pendente
            }
            Text(text = "Valor: $paymentText", style = MaterialTheme.typography.bodyMedium)

            Row(modifier = Modifier.padding(top = 8.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                OutlinedButton(onClick = { onNavigateToEdit(os) }) {
                    Text("Editar")
                }
                if (os.status != "REPARADO" && os.status != "ENTREGUE") {
                    Button(onClick = { onUpdateStatus("REPARADO") }) {
                        Text("Concluído")
                    }
                }
                if (os.status == "REPARADO") {
                    Button(onClick = { onUpdateStatus("ENTREGUE") }) {
                        Text("Entregar")
                    }
                }
            }
        }
    }
}
