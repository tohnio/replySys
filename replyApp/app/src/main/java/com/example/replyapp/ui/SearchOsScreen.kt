package com.example.replyapp.ui

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Close
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.example.replyapp.api.OrdemServico

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SearchOsScreen(
    onBack: () -> Unit,
    onNavigateToEdit: (OrdemServico) -> Unit,
    viewModel: HomeViewModel = viewModel()
) {
    var searchQuery by remember { mutableStateOf("") }

    LaunchedEffect(Unit) {
        viewModel.loadOrdens()
    }

    val filteredOrdens = remember(searchQuery, viewModel.ordens) {
        viewModel.ordens.filter { os ->
            val nomeMatch = os.cliente?.nome?.contains(searchQuery, ignoreCase = true) ?: false
            val modeloMatch = os.modelo?.contains(searchQuery, ignoreCase = true) ?: false
            nomeMatch || modeloMatch
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Voltar")
                    }
                },
                title = {
                    TextField(
                        value = searchQuery,
                        onValueChange = { searchQuery = it },
                        placeholder = { Text("Pesquisar por nome ou modelo...") },
                        singleLine = true,
                        colors = TextFieldDefaults.colors(
                            focusedContainerColor = androidx.compose.ui.graphics.Color.Transparent,
                            unfocusedContainerColor = androidx.compose.ui.graphics.Color.Transparent,
                            disabledContainerColor = androidx.compose.ui.graphics.Color.Transparent,
                        ),
                        trailingIcon = {
                            if (searchQuery.isNotEmpty()) {
                                IconButton(onClick = { searchQuery = "" }) {
                                    Icon(Icons.Default.Close, contentDescription = "Limpar")
                                }
                            }
                        },
                        modifier = Modifier.fillMaxWidth()
                    )
                }
            )
        }
    ) { padding ->
        if (filteredOrdens.isEmpty()) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(padding),
                contentAlignment = Alignment.Center
            ) {
                Text(
                    text = if (searchQuery.isEmpty()) "Digite algo para pesquisar" else "Nenhum resultado encontrado",
                    style = MaterialTheme.typography.bodyLarge,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        } else {
            LazyColumn(contentPadding = padding, modifier = Modifier.fillMaxSize()) {
                items(filteredOrdens) { os ->
                    OsItemCard(
                        os = os,
                        onNavigateToEdit = onNavigateToEdit,
                        onUpdateStatus = { status -> viewModel.updateStatus(os.id, status) }
                    )
                }
            }
        }
    }
}
